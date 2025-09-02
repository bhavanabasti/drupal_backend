<?php

namespace Drupal\qrcode_fields\Service;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\token\Token;

/**
 * QRImage service.
 *
 * This service is used for generating QR images based
 * on the 'qrcode_fields' plugin.
 */
class QRImage implements QRImageInterface {

  use StringTranslationTrait;

  /**
   * QR code plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Plugin ID to use for generating image.
   *
   * @var string
   *   Default: "qchart".
   */
  protected $pluginId = 'gchart';

  /**
   * Token service.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   QR code plugin manager.
   * @param \Drupal\token\Token $token
   *   Token service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(PluginManagerInterface $pluginManager, Token $token, ConfigFactoryInterface $configFactory) {
    $this->pluginManager = $pluginManager;
    $this->token = $token;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $data, $width, $height) {
    $build = [];

    $field_type = $data['field_type'] ?? '';
    $plugin_id = $data['plugin_id'] ?? '';
    $alt = '';

    if ($this->pluginManager->hasDefinition($this->pluginId)) {
      $qr_text = '';
      switch ($field_type) {
        case 'qrcode_email':
          $email = '';
          $subject = '';
          $message = '';

          if (isset($data['email'])) {
            $email = $this->token->replace(
            $data['email'] ?? $this->t('Missing QR data email.'),
            $data['objects'] ?? []
            );
          }

          if (isset($data['subject'])) {
            $subject = $this->token->replace(
            $data['subject'] ?? $this->t('Missing QR data subject.'),
            $data['objects'] ?? []
            );
          }

          if (isset($data['message'])) {
            $message = $this->token->replace(
            $data['message'] ?? $this->t('Missing QR data message.'),
            $data['objects'] ?? []
            );
          }

          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('Send email');

          }
          else {
            $alt = $this->t('Email: @email', ['@email' => $email]) . '<br>';
            $alt .= $this->t('Subject: @subject', ['@subject' => $subject]) . '<br>';
            $alt .= $this->t('Message: @message', ['@message' => $message]) . '<br>';
          }

          $qr_text = "MATMSG:TO:{$email};SUB:{$subject};BODY:{$message};;";

          break;

        case 'qrcode_mecard':
          $fname = '';
          $lname = '';
          $email = '';
          $phone = '';
          $address = '';
          $url = '';
          $note = '';
          $organization = '';
          $birthday = '';

          if (isset($data['fname'])) {
            $fname = $this->token->replace(
            $data['fname'] ?? $this->t('Missing QR data fname.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['lname'])) {
            $lname = $this->token->replace(
            $data['lname'] ?? $this->t('Missing QR data lname.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['email'])) {
            $email = $this->token->replace(
            $data['email'] ?? $this->t('Missing QR data email.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['phone'])) {
            $phone = $this->token->replace(
            $data['phone'] ?? $this->t('Missing QR data phone.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['address'])) {
            $address = $this->token->replace(
            $data['address'] ?? $this->t('Missing QR data address.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['url'])) {
            $url = $this->token->replace(
            $data['url'] ?? $this->t('Missing QR data url.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['note'])) {
            $note = $this->token->replace(
            $data['note'] ?? $this->t('Missing QR data note.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['organization'])) {
            $organization = $this->token->replace(
            $data['organization'] ?? $this->t('Missing QR data organization.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['birthday'])) {
            $birthday = $this->token->replace(
            $data['birthday'] ?? $this->t('Missing QR data birthday.'),
            $data['objects'] ?? []
            );
          }

          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('Send email');
          }
          else {
            $alt .= $this->t('First name: @fname', ['@fname' => $fname]) . '<br>';
            $alt .= $this->t('Last name: @lname', ['@lname' => $lname]) . '<br>';
            $alt .= $this->t('Email: @email', ['@email' => $email]) . '<br>';
            $alt .= $this->t('Phone: @phone', ['@phone' => $phone]) . '<br>';
            $alt .= $this->t('Address: @address', ['@address' => $address]) . '<br>';
            $alt .= $this->t('URL: @url', ['@url' => $url]) . '<br>';
            $alt .= $this->t('Note: @note', ['@note' => $note]) . '<br>';
            $alt .= $this->t('Organization: @organization', ['@organization' => $organization]) . '<br>';
            $alt .= $this->t('Birthday: @birthday', ['@birthday' => $birthday]) . '<br>';
          }

          $qr_text = "MECARD:N:{$lname},{$fname};ADR:{$address};TEL:{$phone};EMAIL:{$email};NOTE:{$note};URL:{$url};ORG:{$organization};BDAY:{$birthday};;";

          break;

        case 'qrcode_phone':
          $phone = '';
          if (isset($data['phone'])) {
            $phone = $this->token->replace(
            $data['phone'] ?? $this->t('Missing QR data phone.'),
            $data['objects'] ?? []
            );
          }

          $alt = $this->t('Phone: @phone', ['@phone' => $phone]);

          $qr_text = "TEL:{$phone}";
          break;

        case 'qrcode_sms':
          $phone = '';
          $message = '';
          if (isset($data['phone'])) {
            $phone = $this->token->replace(
            $data['phone'] ?? $this->t('Missing QR data phone.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['message'])) {
            $message = $this->token->replace(
            $data['message'] ?? $this->t('Missing QR data message.'),
            $data['objects'] ?? []
            );
          }

          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('Send SMS');
          }
          else {
            $alt = $this->t('Phone: @phone<br>', ['@phone' => $phone]);
            if ($message) {
              $alt .= $this->t('Message: @message<br>', ['@message' => $message]);
            }
          }

          $qr_text = "SMSTO:{$phone}:{$message}";

          break;

        case 'qrcode_text':
          $qr_text = '';
          if (isset($data['text'])) {
            $qr_text = $this->token->replace(
            $data['text'] ?? $this->t('Missing QR data text.'),
            $data['objects'] ?? []
            );
          }
          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('@qr_text', ['@qr_text' => $qr_text]);
          }
          else {
            $alt = 'Text: ' . $qr_text . '<br>';
            $alt = $this->t('@alt', ['@alt' => $alt]);
          }

          break;

        case 'qrcode_url':
          $qr_text = '';
          if (isset($data['url'])) {
            $qr_text = $this->token->replace(
            $data['url'] ?? $this->t('Missing QR data url.'),
            $data['objects'] ?? []
            );
          }
          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('@qr_text', ['@qr_text' => $qr_text]);
          }
          else {
            $alt = 'URL: ' . $qr_text . '<br>';
            $alt = $this->t('@alt', ['@alt' => $alt]);
          }

          break;

        case 'qrcode_vcard':
          $fname = '';
          $lname = '';
          $email = '';
          $phone = '';
          $work_phone = '';
          $address = '';
          $url = '';
          $note = '';
          $organization = '';
          $title = '';
          $birthday = '';

          if (isset($data['fname'])) {
            $fname = $this->token->replace(
            $data['fname'] ?? $this->t('Missing QR data fname.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['lname'])) {
            $lname = $this->token->replace(
            $data['lname'] ?? $this->t('Missing QR data lname.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['email'])) {
            $email = $this->token->replace(
            $data['email'] ?? $this->t('Missing QR data email.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['phone'])) {
            $phone = $this->token->replace(
            $data['phone'] ?? $this->t('Missing QR data phone.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['work_phone'])) {
            $work_phone = $this->token->replace(
            $data['work_phone'] ?? $this->t('Missing QR data work phone.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['address'])) {
            $address = $this->token->replace(
            $data['address'] ?? $this->t('Missing QR data address.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['url'])) {
            $url = $this->token->replace(
            $data['url'] ?? $this->t('Missing QR data url.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['note'])) {
            $note = $this->token->replace(
            $data['note'] ?? $this->t('Missing QR data note.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['organization'])) {
            $organization = $this->token->replace(
            $data['organization'] ?? $this->t('Missing QR data organization.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['title'])) {
            $title = $this->token->replace(
            $data['title'] ?? $this->t('Missing QR data title.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['birthday'])) {
            $birthday = $this->token->replace(
            $data['birthday'] ?? $this->t('Missing QR data birthday.'),
            $data['objects'] ?? []
            );
          }

          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('Add to contact');
          }
          else {
            $alt .= 'First name: ' . $fname . '<br>';
            if ($lname) {
              $alt .= 'Last name: ' . $lname . '<br>';
            }
            if ($email) {
              $alt .= 'Email: ' . $email . '<br>';
            }
            $alt .= 'Phone: ' . $phone . '<br>';
            if ($work_phone) {
              $alt .= 'Work phone: ' . $work_phone . '<br>';
            }
            if ($address) {
              $alt .= 'Address: ' . $address . '<br>';
            }
            if ($url) {
              $alt .= 'URL: ' . $url . '<br>';
            }
            if ($note) {
              $alt .= 'Note: ' . $note . '<br>';
            }
            if ($organization) {
              $alt .= 'Organization: ' . $organization . '<br>';
            }
            if ($title) {
              $alt .= 'Job title: ' . $title . '<br>';
            }
            if ($birthday) {
              $alt .= 'Birthday: ' . $birthday . '<br>';
            }

            $alt = $this->t('@alt', ['@alt' => $alt]);
          }

          $qr_text = sprintf(
          "BEGIN:VCARD\nVERSION:3.0\nN:%s;%s\nFN:%s %s\nORG:%s\nTITLE:%s\nADR:%s\nTEL;WORK:%s\nTEL;CELL:%s\nEMAIL:%s\nURL:%s\nEND:VCARD",
          $lname, $fname, $fname, $lname, $organization, $title, $address, $work_phone, $phone, $email, $url
          );

          break;

        case 'qrcode_event':
          $summary = '';
          $description = '';
          $location = '';
          $dstart = '';
          $dend = '';
          $dstart_formatted = '';
          $dend_formatted = '';

          $timezone = $this->configFactory->get('system.date')->get('timezone.default');

          if (isset($data['summary'])) {
            $summary = $this->token->replace(
            $data['summary'] ?? $this->t('Missing QR data summary.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['description'])) {
            $description = $this->token->replace(
            $data['description'] ?? $this->t('Missing QR data description.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['location'])) {
            $location = $this->token->replace(
            $data['location'] ?? $this->t('Missing QR data location.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['dstart'])) {
            $dstart = $this->token->replace(
            $data['dstart'] ?? $this->t('Missing QR data dstart.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['dend'])) {
            $dend = $this->token->replace(
            $data['dend'] ?? $this->t('Missing QR data dend.'),
            $data['objects'] ?? []
            );
          }

          $drupalDateTime = new DrupalDateTime($dstart);
          $dstart_qr = $drupalDateTime->format('Ymd\THis\Z');

          if ($dend) {
            $drupalDateTime = new DrupalDateTime($dend);
            $dend_qr = $drupalDateTime->format('Ymd\THis\Z');
          }

          if ($dstart) {
            $utcDateTime = new DrupalDateTime($dstart, 'UTC');
            $customTimezone = new \DateTimeZone($timezone);
            $utcDateTime->setTimezone($customTimezone);
            $dstart_formatted = $utcDateTime->format('Y-m-d H:i:s');
          }

          if ($dend) {
            $utcDateTime = new DrupalDateTime($dend, 'UTC');
            $customTimezone = new \DateTimeZone($timezone);
            $utcDateTime->setTimezone($customTimezone);
            $dend_formatted = $utcDateTime->format('Y-m-d H:i:s');
          }

          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('Add event to calendar');
          }
          else {
            $alt .= 'Event title: ' . $summary . '<br>';
            if ($description) {
              $alt .= 'Description: ' . $description . '<br>';
            }
            if ($location) {
              $alt .= 'Location: ' . $location . '<br>';
            }
            $alt .= 'Start date: ' . $dstart_formatted . '<br>';
            if ($dend_formatted) {
              $alt .= 'End date: ' . $dend_formatted . '<br>';
            }
            $alt .= 'Timezone: ' . $timezone . '<br>';

            $alt = $this->t('@alt', ['@alt' => $alt]);
          }

          $qr_text = sprintf(
              "BEGIN:VCALENDAR\nBEGIN:VEVENT\nSUMMARY:%s\nDESCRIPTION:%s\nLOCATION:%s\nDTSTART:%s\nDTEND:%s\nTZID:%s\nEND:VEVENT\nEND:VCALENDAR",
              $summary,
              $description,
              $location,
              $dstart_qr,
              $dend ? $dend_qr : $dstart_qr,
              $timezone
          );

          break;

        case 'qrcode_wifi':
          $network_name = '';
          $password = '';
          $hidden = '';
          $encryption = '';

          if (isset($data['network_name'])) {
            $network_name = $this->token->replace(
            $data['network_name'] ?? $this->t('Missing QR data network name.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['password'])) {
            $password = $this->token->replace(
            $data['password'] ?? $this->t('Missing QR data password.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['hidden'])) {
            $hidden = $this->token->replace(
            $data['hidden'] ?? $this->t('Missing QR data hidden.'),
            $data['objects'] ?? []
            );
          }
          if (isset($data['encryption'])) {
            $encryption = $this->token->replace(
            $data['encryption'] ?? $this->t('Missing QR data encryption.'),
            $data['objects'] ?? []
            );
          }

          if ($plugin_id == 'qrcode_fields_formatter_url') {
            $alt = $this->t('WiFi Network');
          }
          else {
            $alt = $this->t('Network name: @network_name<br>', ['@network_name' => $network_name]);

            if ($password) {
              $alt .= $this->t('Password: @password<br>', ['@password' => $password]);
            }

            if ($hidden) {
              $alt .= $this->t('Hidden: @hidden<br>', ['@hidden' => $hidden]);
            }

            $alt .= $this->t('Encryption: @encryption<br>', ['@encryption' => $encryption]);
          }

          if ($hidden) {
            $hidden = 'H:true;';
          }
          else {
            $hidden = ';';
          }

          $qr_text = "WIFI:T:{$encryption};S:{$network_name};P:{$password};{$hidden};";

          break;
      }

      $pluginInstance = $this->pluginManager->createInstance($this->pluginId, [
        'data' => $qr_text,
        'image_width' => $width,
        'image_height' => $height,
      ]);
      $build['#theme'] = 'image';
      $build['#uri'] = $pluginInstance->getUrl()->toString();
      $build['#alt'] = $alt ?? '';
    }
    else {
      $build['#markup'] = $this->t('Failed to render QR image using plugin: @plugin.', [
        '@plugin' => $this->pluginId,
      ]);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($pluginId) {
    $this->pluginId = $pluginId;
    return $this;
  }

}
