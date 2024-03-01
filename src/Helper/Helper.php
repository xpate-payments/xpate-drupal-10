<?php

namespace Drupal\commerce_ginger\Helper;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class Helper.
 *
 * This class needs to implement methods which could be used by several classes
 *
 * @package Drupal\commerce_ginger\Helper
 */
class Helper
{
  use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    public function getValueFromIssuersArrayById(array $issuers, string $id)
    {
        $issuersValues = [];
        foreach ($issuers as $issuer) {
            $issuersValues[] = $issuer[$id];
        }
        return $issuersValues;
    }

    public function setDefaultButtons(array $form)
    {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Complete payment')
      ];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => Url::fromUri($form['#cancel_url']),
      ];
      return $form;
    }

    public function setBirthday(array $form)
    {
      $form['birthdate'] = [
        '#type' => 'date',
        '#title' => $this->t('Please, set your birthday date'),
        '#required' => true
      ];
      return $form;
    }

    public function setGender(array $form)
    {
      $form['gender'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose gender'),
        '#options' => [$this->t('Male'), $this->t('Female')],
        '#value' => ['male', 'female']
      ];
      return $form;
    }

    public function setTermsLink(array $form, $link)
    {
      $form['verified_terms_of_service'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I accept '). '<a href="'.$link.'" target="_blank">'.$this->t('Terms and Conditions').'</a>',
        '#required' => true
      ];
      return $form;
    }

    public function setBanktransferForm(array $form, array $orderArray)
    {
//      dd(json_encode());
      $form['banktransfer_info'] = array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' =>
          '
          <div style="
            margin: auto;
            width: 50%;
            padding: 10px;
            ">
            <p>'.$this->t("Your order has been received. Thank you for your purchase! We will dispatch your order as soon as possible. For any questions, please contact our customer support.").'</p>
            <p>'.$this->t("Please use the following payment information:").'</p>
            <table border="1" style="margin-left: auto; margin-right: auto;">
              <tr>
                <td>'.$this->t("Amount").'</td>
                <td>'.($orderArray["amount"]/100).' '.$orderArray["currency"].'</td>
              </tr>
              <tr>
                <td>'.$this->t("Reference").'</td>
                <td>'.current($orderArray["transactions"])["payment_method_details"]["reference"].'</td>
              </tr>
              <tr>
                <td>IBAN</td>
                <td>'.current($orderArray["transactions"])["payment_method_details"]["creditor_iban"].'</td>
              </tr>
              <tr>
                <td>BIC</td>
                <td>'.current($orderArray["transactions"])["payment_method_details"]["creditor_bic"].'</td>
              </tr>
               <tr>
                <td>'.$this->t("Account holder").'</td>
                <td>'.current($orderArray["transactions"])["payment_method_details"]["creditor_account_holder_name"].'</td>
              </tr>
               <tr>
                <td>'.$this->t("Country").'</td>
                <td>'.current($orderArray["transactions"])["payment_method_details"]["creditor_account_holder_country"].'</td>
              </tr>
               <tr>
                <td>'.$this->t("City").'</td>
                <td>'.current($orderArray["transactions"])["payment_method_details"]["creditor_account_holder_city"].'</td>
              </tr>
            </table>
            <p>'.$this->t("(!) Don`t forget to use the reference in your payment. Without the reference the processing of your payment can take more time!").'</p>
            </div>',
      );

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Complete payment')
      ];
      return $form;
    }
}
