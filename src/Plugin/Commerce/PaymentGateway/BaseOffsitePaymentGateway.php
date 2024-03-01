<?php

namespace Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Drupal\commerce_ginger\Builder\OrderBuilder;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_price\MinorUnitsConverterInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GingerPluginSdk\Exceptions\CaptureFailedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal;
use Drupal\commerce_ginger\Controller\Webhook;
use Drupal\commerce_ginger\Redefiner\BuilderRedefiner;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\commerce_ginger\PSP\PSPconfig;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_ginger\Helper\OrderHelper;
use Drupal\commerce_ginger\Helper\Helper;
use GingerPluginSdk\Properties\Amount;

class BaseOffsitePaymentGateway extends OffsitePaymentGatewayBase implements SupportsRefundsInterface, SupportsAuthorizationsInterface
{
    use Drupal\commerce_ginger\RedirectTrait;

    /**
     * Module log setting.
     */
    protected $log;

    /**
     * var Webhook.
     */
    protected $webhook;

    /**
     * var Helper.
     */
    protected $helper;

    /**
     * var BuilderRedefiner
     */
    protected $builderRedefiner;

    public function __construct(
        array                        $configuration,
                                     $plugin_id,
                                     $plugin_definition,
        EntityTypeManagerInterface   $entity_type_manager,
        PaymentTypeManager           $payment_type_manager,
        PaymentMethodTypeManager     $payment_method_type_manager,
        TimeInterface                $time,
        MinorUnitsConverterInterface $minor_units_converter = NULL
    )
    {
        PSPconfig::registerStrategies();
        $this->webhook = new Webhook();
        $this->builderRedefiner = new OrderBuilder();
        $this->helper = new Helper();

        parent::__construct(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $entity_type_manager,
            $payment_type_manager,
            $payment_method_type_manager,
            $time,
            $minor_units_converter
        );
    }

    /**
     * Checks whether the given payment can be refunded.
     *
     * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
     *   The payment to refund.
     *
     * @return bool
     *   TRUE if the payment can be refunded, FALSE otherwise.
     */
    public function canRefundPayment(PaymentInterface $payment)
    {
        return true;
    }

    /**
     * Checks whether the given payment can be captured.
     *
     * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
     *   The payment to capture.
     *
     * @return bool
     *   TRUE if the payment can be captured, FALSE otherwise.
     */
    public function canCapturePayment(PaymentInterface $payment)
    {
        return false;
    }

    /**
     * Refunds the given payment.
     *
     * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
     *   The payment to refund.
     * @param \Drupal\commerce_price\Price $amount
     *   The amount to refund. If NULL, defaults to the entire payment amount.
     *
     * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
     *   Thrown when the transaction fails for any reason.
     */
    public function refundPayment(PaymentInterface $payment, Price $amount = NULL)
    {
        $client = $this->builderRedefiner->getClient();
        if (OrderHelper::isOrderRefunded($client->getOrder($payment->getRemoteId()))) {
            throw  new Drupal\commerce_payment\Exception\PaymentGatewayException($this->t('Order already refunded!'));
        }
        try {
            $client->refundOrder($payment->getRemoteId(), new Amount($amount->getNumber() * 100));
        } catch (\Exception $exception) {
            \Drupal::logger(PSPconfig::getLoggerChanel())->error($exception);
            if ($exception->getMessage()) {
                throw  new Drupal\commerce_payment\Exception\PaymentGatewayException($exception->getMessage());
            }
            throw  new Drupal\commerce_payment\Exception\PaymentGatewayException($this->t('Order is not yet captured, only captured order could be refunded!'));
        }

        $payment->setState('refunded');
        $payment->save();
    }

    public function capturePayment(PaymentInterface $payment, Price $amount = NULL)
    {
        $client = $this->builderRedefiner->getClient();
        if (!OrderHelper::isOrderCapturable($client->getOrder($payment->getRemoteId()))) {
            throw  new Drupal\commerce_payment\Exception\PaymentGatewayException($this->t('Order do not require capturing'));
        }

        try {
            $client->captureOrderTransaction($payment->getRemoteId());
        } catch (\Exception $exception) {
            \Drupal::logger(PSPconfig::getLoggerChanel())->error($exception);
            if ($client->getOrder($payment->getRemoteId())->getStatus() == 'completed') {
                throw  new Drupal\commerce_payment\Exception\PaymentGatewayException($this->t('Order should be completed'));
            }
            throw  new Drupal\commerce_payment\Exception\PaymentGatewayException($this->t('Capturing failed'));
        }

        $payment->setState('captured');
        $payment->save();
    }

    public function voidPayment(PaymentInterface $payment)
    {
        return false;
    }

    public function defaultConfiguration()
    {
//    var_dump($this->getPluginId());
//    \Drupal::messenger()->addMessage($this->t('Your order is processing. Thanks!'));
        return parent::defaultConfiguration(); // TODO: Change the autogenerated stub
    }

    public function onReturn(OrderInterface $order, Request $request)
    {
        /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $transaction_id = $request->query->get('order_id');
        $payment = $payment_storage->loadByRemoteId($transaction_id);
        $payment->getOrder()->setOrderNumber($payment->getOrderId());
        $payment->save();

        $this->webhook->processOrderStatus($transaction_id, $payment);

        parent::onReturn($order, $request);
    }

    /**
     * Check if successful and reply.
     *
     * {@inheritdoc}
     */
    public function onNotify(Request $request)
    {
        $input = null;
        try {
            $input = json_decode(file_get_contents("php://input"), true);
        } catch (\Exception $e) {
            \Drupal::logger(PSPconfig::getLoggerChanel())->error($exception);
        }
        if (isset($input['order_id'])) {
            $this->webhook->processWebhook($input, $this->entityTypeManager);
        }
        parent::onNotify($request);
    }

    /**
     * This method allow to add forms on checkout page.
     *
     * {@inheritdoc}
     */
    public function prepareForm(array $form)
    {
        return false;
    }
}
