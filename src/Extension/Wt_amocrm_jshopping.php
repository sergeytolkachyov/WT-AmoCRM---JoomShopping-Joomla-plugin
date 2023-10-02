<?php
/**
 * @package     WT Amo CRM JoomShopping
 * @version     1.0.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0.0
 */

// No direct access
namespace Joomla\Plugin\System\Wt_amocrm_jshopping\Extension;
defined('_JEXEC') or die;

use Joomla\Application\SessionAwareWebApplicationInterface;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Event\SubscriberInterface;
use Webtolk\Amocrm\Amocrm;

class Wt_amocrm_jshopping extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadlanguage = true;

	protected $allowLegacyListeners = false;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterRender' => 'onAfterRender',
			'onAfterCreateOrderFull' => 'onAfterCreateOrderFull',
			'onAfterDispatch' => 'onAfterDispatch',
			'onBeforeDisplayCheckoutFinish' => 'onBeforeDisplayCheckoutFinish',
		];
	}


	/**
	 * @param string $debug_section_header
	 * @param mixed $debug_data
	 *
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function prepareDebugInfo(string $debug_section_header, $debug_data): void
	{
		if ($this->params->get('debug') == 1) {
			// Берем сессию только в HTML фронте
			$session = ($this->getApplication() instanceof SessionAwareWebApplicationInterface ? $this->getApplication()->getSession() : null);
			if (is_array($debug_data) || is_object($debug_data)) {
				$debug_data = print_r($debug_data, true);
			}
			$debug_output = $session->get("amocrmjshoppingdebugoutput");

			$debug_output .= "<details style='border:1px solid #0FA2E6; margin-bottom:5px;'>";
			$debug_output .= "<summary style='background-color:#384148; color:#fff;'>" . $debug_section_header . "</summary>";
			$debug_output .= "<pre style='background-color: #eee; padding:10px;'>";
			$debug_output .= $debug_data;
			$debug_output .= "</pre>";
			$debug_output .= "</details>";

			$session->set("amocrmjshoppingdebugoutput", $debug_output);
		}
	}// END prepareDebugInfo

	/**
	 * Create an order which might be 'created' or 'not created'. 'Created' orders are displaying in JoomShopping admin panel.
	 * 'Not created' orders are hidden by filter.
	 *
	 * @param $order object JoomShopping order object
	 * @param $cart  object JoomShopping cart object
	 *
	 * @since 1.0.0
	 */
	public function onAfterCreateOrderFull($event): void
	{
		$order = $event->getArgument(0);
		// $cart = $event->getArgument(1);

		if ($this->params->get('order_trigger_event', 'always') == 'always') {
			$this->sendOrderToAmocrm($order->order_id);
		}

	}// end onAfterCreateOrderFull()

	/**
	 * Функция выполняет основную работу по добавлению заказа в Битрикс 24.
	 *
	 * @param $orderId
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function sendOrderToAmocrm($orderId) : void
	{

		if (empty($orderId)) {
			return;
		}
		if (!class_exists('JSFactory')) {
			require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
		}
		$this->loadLanguage();
		// Берем сессию только в HTML фронте
		$session = ($this->getApplication() instanceof SessionAwareWebApplicationInterface ? $this->getApplication()->getSession() : null);

		$order = \JSFactory::getTable('order', 'jshop');
		$order->load($orderId);
		$orderItems = $order->getAllItems();

		$lead_data = [
			'created_by' => 0, //ID пользователя, создающий сделку. При передаче значения 0, сделка будет считаться созданной роботом. Поле не является обязательным
			'name' => (!empty($this->params->get('order_name_prefix')) ? $this->params->get('order_name_prefix') . ' ' . $order->order_number : $order->order_number),
			'pipeline_id' => $this->params->get('pipeline_id'),
			'price' => (int)$order->order_total,
		];

		$f_name = (!empty($order->d_f_name) ? $order->d_f_name : $order->f_name);//Имя
		$l_name = (!empty($order->d_l_name) ? $order->d_l_name : $order->l_name);//Фамилия
		$m_name = (!empty($order->d_m_name) ? $order->d_m_name : $order->m_name);//Отчество
		$firma_name = (!empty($order->d_firma_name) ? $order->d_firma_name : $order->firma_name);//Название фирмы
		$client_type = $order->client_type;//Тип клиента: юрик или физик
		$firma_code = $order->firma_code;//Фирма: ИНН?
		$street = (!empty($order->d_street) ? $order->d_street : $order->street);//Улица
		$street_nr = (!empty($order->d_street_nr) ? $order->d_street_nr : $order->street_nr);//номер дома
		$home = (!empty($order->d_home) ? $order->d_home : $order->home);//дом
		$apartment = (!empty($order->d_apartment) ? $order->d_apartment : $order->apartment);//квартира/офис
		$zip = (!empty($order->d_zip) ? $order->d_zip : $order->zip);//индекс получателя
		$city = (!empty($order->d_city) ? $order->d_city : $order->city);//город
		$state = (!empty($order->d_state) ? $order->d_state : $order->state);//область/регион
		$phone = (!empty($order->d_phone) ? $order->d_phone : $order->phone);//телефон
		$mobil_phone = (!empty($order->d_mobil_phone) ? $order->d_mobil_phone : $order->mobil_phone);//телефон мобильный
		$contact = [
			'name' => 'Joomshopping customer from order #' . $order->order_number
		];


		if (isset($f_name) && !empty($f_name)) {
			$contact['name'] = $f_name;
			$contact['first_name'] = $f_name;

		}
		/**
		 * @todo А если окажется, что имя из основных данных, а фамилия - из данных о доставке?
		 */
		if (isset($l_name) && !empty($l_name)) {
			$contact['name'] = (isset($f_name) && !empty($f_name)) ? $order->f_name . ' ' . $l_name : $l_name;
			$contact['last_name'] = $l_name;

		}
		/**
		 * API Amo CRM очень привередливо. Не приемлет пустых значений или неправильные типы.
		 * Поэтому проверяем на всё, что возможно перед отправкой.
		 */
		if ((isset($phone) && !empty($phone))
			|| (isset($mobil_phone) && !empty($mobil_phone))
		) {
			/**
			 * Телефоны контакта
			 */
			$phones = [
				'field_code' => 'PHONE',
				'values' => []
			];

			if (isset($phone) && !empty($phone)) {
				$phones['values'][] = [
					'enum_code' => 'WORK',
					'value' => $phone
				];
			}

			if (isset($mobil_phone) && !empty($mobil_phone)) {
				$phones['values'][] = [
					'enum_code' => 'WORK',
					'value' => $mobil_phone
				];
			}
			$contact['custom_fields_values'][] = $phones;
		}

		if ((isset($order->email) && !empty($order->email))
			|| (isset($order->d_email) && !empty($order->d_email))
		) {
			/**
			 * E-mails контакта
			 */
			$emails = [
				'field_code' => 'EMAIL',
				'values' => []
			];

			if (isset($order->email) && !empty($order->email)) {
				$emails['values'][] = [
					'enum_code' => 'WORK',
					'value' => $order->email
				];
			}

			if (isset($order->d_email) && !empty($order->d_email) && $order->d_email !== $order->email) {
				$emails['values'][] = [
					'enum_code' => 'WORK',
					'value' => $order->d_email
				];
			}
			$contact['custom_fields_values'][] = $emails;
		}

		$this->prepareDebugInfo('custom fields', $this->params->get('fields'));
		/**
		 * Кастомные поля из настроек сопоставления
		 */
		$amo_to_jshopping_fields = (array)$this->params->get('fields');

		if (is_countable($amo_to_jshopping_fields) && count($amo_to_jshopping_fields) > 0) {

			foreach ($amo_to_jshopping_fields as $key => $value) {
				$jshopping_field_value = '';
				foreach ($value->storefield as $jshopping_field) {

					if (empty($order->$jshopping_field)) {
						continue;
					}

					if ($jshopping_field == 'country') {//Получаем название страны

						$jshopping_field_value .= (string)$this->getCountryName($order->$value) . ' ';

					} elseif ($jshopping_field == 'coupon_id') {// Получаем код купона

						$jshopping_field_value .= $order->getCouponCode() . ' ';

					} elseif ($jshopping_field == 'shipping_method_id') {//название способа доставки

						$jshopping_field_value .= $order->getShippingName() . ' ';

					} elseif ($jshopping_field == 'payment_method_id') {//название способа оплаты

						$jshopping_field_value .= $order->getPaymentName() . ' ';

					} elseif ($jshopping_field == 'order_status') {//название статуса заказа

						$jshopping_field_value .= $order->getStatus() . ' ';

					} elseif ($jshopping_field == 'birthday' and ($order->$jshopping_field == '0000-00-00' || $order->$jshopping_field == '')) {

						continue;

					} elseif ($value == 'wt_sm_otpravka_pochta_ru_barcode') {// трек-номер Почты России - WT SM Otpravka.pochta.ru

						$jshopping_field_value .= (string)$session->get('wt_sm_otpravka_pochta_ru_barcode');

					} else {

						$jshopping_field_value .= (string)$order->$jshopping_field . ' ';

					}

				}

				if (empty($jshopping_field_value)) {
					continue;
				}

				$lead_custom_field_array = [
					'field_id' => (int)$value->amocrm_field_id,
					'values' => [
						[
							'value' => $jshopping_field_value
						]
					]
				];
				$lead_data["custom_fields_values"][] = $lead_custom_field_array;
			}
		}

		$lead_data['_embedded']['contacts'][] = $contact;


		if ($this->params->get('lead_tag_id', 0) > 0) {
			$lead_data['_embedded']['tags'][0]['id'] = (int)$this->params->get('lead_tag_id');
		}

		/**
		 * Add UTMs into array
		 */

		$lead_data = $this->checkUtms($lead_data);
		$leads[] = $lead_data;
		$this->prepareDebugInfo('Amo CRM lead data before sending', $lead_data);
		/**
		 * Create a lead
		 */
		$amocrm = new Amocrm();
		$result = $amocrm->createLeadsComplex($leads);
		$result = (array)$result;

		$this->prepareDebugInfo('Amo CRM result info', $result);

		if ($result['error_code'] && $result['error_code']) {
			$this->saveToLog('WT Amo CRM - JoomShopping: ' . print_r($result, true), 'ERROR');
		}

		if (!isset($result['error_code'])) {
			$lead_id = $result[0]->id;
//		$contact_id = $result[0]->contact_id;

			$notes = [];

			if (!empty($order->order_add_info) && $this->params->get('amocrm_note_add_order_add_info', 1)) {
				/**
				 * note_type - 'common' - обычное текстовое.
				 *           - 'extended_service_message' - Расширенное системное сообщение (поддерживает больше текста и сворачивается в интерфейсе). Если используется extended_service_message, то в params должен быть ключ 'service' = название сервиса от имени которого сообщение
				 */
				$notes[] = [
					'created_by' => 0, // 0 - создал робот
					'note_type' => 'common',
					'params' => [
						'text' => Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ADD_ORDER_ADD_INFO_API') . $order->order_add_info,
					]
				];
			}
			// Сумма заказа в примечание
			$notes[] = [
				'created_by' => 0, // 0 - создал робот
				'note_type' => 'common',
				'params' => [
					'text' => Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_TOTAL_API_PREPEND') . \JSHelper::formatprice($order->order_total) . Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_TOTAL_API_APPEND'),
				]
			];

			if (count($orderItems) > 0 && $this->params->get('amocrm_note_order_items', 1) == 1) {
				$products_for_comment = '';
				foreach ($orderItems as $item) {
					$products_for_comment .= Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_ITEMS_PRODUCT') . $item->product_name . PHP_EOL;
					$products_for_comment .= Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_ITEMS_QUANTITY') . $item->product_quantity . PHP_EOL;
					$products_for_comment .= Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_ITEMS_PRICE') . $item->product_item_price . PHP_EOL;
					if (!empty($item->product_attributes)) {
						$products_for_comment .= Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_ITEMS_PRODUCT_ATTRS') . $item->product_attributes . PHP_EOL;
					}
					if (!empty($item->weight)) {
						$products_for_comment .= Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_ITEMS_PRODUCT_WEIGHT') . $item->weight . PHP_EOL;
					}

					$products_for_comment .= PHP_EOL . ' ==== ' . PHP_EOL . PHP_EOL;

				}

				$notes[] = [
					'created_by' => 0, // 0 - создал робот
					'note_type' => 'common',
					'params' => [
						'text' => Text::_('PLG_WT_AMOCRM_JSHOPPING_AMOCRM_NOTE_ORDER_ITEMS') . PHP_EOL . $products_for_comment,
					]
				];
			}
			$this->prepareDebugInfo('Amo CRM NOTEs data before sending', [
				'AmoCrm lead id' => $lead_id,
				'notes' => $notes
			]);

			$notes_result = $amocrm->addNotes('leads', $lead_id, $notes);

			$this->prepareDebugInfo('Amo CRM NOTEs result', [
				'AmoCrm add notes result' => $notes_result
			]);
		}
	}


	/**
	 * Returns country name by id
	 *
	 * @param int $country_id
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function getCountryName($country_id): string
	{
		$lang = $this->getApplication()->getLanguage();
		$current_lang = $lang->getTag();
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('name_' . $current_lang))
			->from($db->quoteName('#__jshopping_countries'))
			->where($db->quoteName('country_id') . ' = ' . $db->quote($country_id));
		$db->setQuery($query);
		$country_name = $db->loadAssoc();

		return (string)$country_name["name_" . $current_lang];
	}


	/**
	 * Function checks the utm marks and set its to array fields
	 *
	 * @param array $lead_data AmoCRM array data
	 *
	 * @return  array  AmoCRM array data with UTMs
	 * @since    1.0.0
	 */
	private function checkUtms(&$lead_data): array
	{
		$utms = array(
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_content',
			'utm_term',
			'fbclid',
			'yclid',
			'gclid',
			'gclientid',
			'from',
			'openstat_source',
			'openstat_ad',
			'openstat_campaign',
			'openstat_service',
			'referrer',
			'roistat',
			'_ym_counter',
			'_ym_uid',
			'utm_referrer'
		);
		foreach ($utms as $key) {
			$utm = $this->getApplication()->getInput()->cookie->get($key, '', 'raw');
			$utm = urldecode($utm);
			$utm_name = strtoupper($key);
			if (!empty($utm)) {
				$utm_array = [
					'field_code' => strtoupper($utm_name),
					'values' => [
						[
							'value' => $utm
						]
					]
				];
				$lead_data["custom_fields_values"][] = $utm_array;
			}

		}

		return $lead_data;
	}


	/**
	 * Добавляем js-скрпиты на HTML-фронт
	 *
	 * @throws \Exception
	 * @since 1.0.0
	 * @see   https://habr.com/ru/articles/672020/
	 * @see   https://habr.com/ru/post/677262/
	 */
	function onAfterDispatch(): void
	{
		if ($this->params->get('use_utm_js_script') == 1) {
			// We are not work in Joomla API or CLI ar Admin area
			if (!$this->getApplication()->isClient('site')) return;

			$doc = $this->getApplication()->getDocument();
			// We are work only in HTML, not JSON, RSS etc.
			if (!($doc instanceof \Joomla\CMS\Document\HtmlDocument)) {
				return;
			}

			$wa = $doc->getWebAssetManager();
			// Show plugin version in browser console from js-script for UTM
			$wt_amocrm_jshopping_plugin_info = simplexml_load_file(JPATH_SITE . "/plugins/system/wt_amocrm_jshopping/wt_amocrm_jshopping.xml");
			$doc->addScriptOptions('plg_system_wt_amocrm_jshopping_version', $wt_amocrm_jshopping_plugin_info->version);
			$wa->registerAndUseScript('plg_system_wt_amocrm_jshopping.wt_amocrm_jshopping_utm', 'plg_system_wt_amocrm_jshopping/wt_amocrm_jshopping_utm.js', ['version' => 'auto', 'relative' => true]);
		}

	}

	/**
	 * Отправляем сделку в Амо СРМ только при успешной оплате.
	 *
	 * @param $text
	 * @param $order_id
	 *
	 * @return void
	 * @throws \Exception
	 * @since 1.0.0
	 */
	public function onBeforeDisplayCheckoutFinish($event): void
	{
		// $text = $event->getArgument(0);
		$order_id = $event->getArgument(1);

		if ($this->params->get('order_trigger_event', 'always') == 'successful_payment') {
			$this->sendOrderToAmocrm($order_id);
		}
	}


	/**
	 * Function for to log library errors in plg_system_Wt_amocrm_jshopping.log.php in
	 * Joomla log path. Default Log category plg_system_Wt_amocrm_jshopping
	 *
	 * @param string $data error message
	 * @param string $priority Joomla Log priority
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function saveToLog(string $data, string $priority = 'NOTICE'): void
	{
		Log::addLogger(
			array(
				// Sets file name
				'text_file' => 'plg_system_wt_amocrm_jshopping.log.php',
			),
			// Sets all but DEBUG log level messages to be sent to the file
			Log::ALL & ~Log::DEBUG,
			array('plg_system_wt_amocrm_jshopping')
		);
		$this->getApplication()->enqueueMessage($data, $priority);
		$priority = 'Log::' . $priority;
		Log::add($data, $priority, 'plg_system_wt_amocrm_jshopping');

	}

	/**
	 * Show debug info for WT SEO Meta templates plugins
	 * on frontend
	 * @return void
	 * @throws \Exception
	 * @since 2.0.0
	 */
	public function onAfterRender(): void
	{
		if (!$this->getApplication()->isClient('site')) {
			return;
		}

		$doc = $this->getApplication()->getDocument();
		if (!($doc instanceof \Joomla\CMS\Document\HtmlDocument)) {
			return;
		}
		if ($this->params->get('debug') == 1) {
			$session = ($this->getApplication() instanceof SessionAwareWebApplicationInterface ? $this->getApplication()->getSession() : null);
			$debug_info = $session->get("amocrmjshoppingdebugoutput");
			if (empty($debug_info)) {
				return;
			}

			$buffer = $this->getApplication()->getBody();
			$html = [];
			$html[] = "<details style='border:1px solid #0FA2E6; margin-bottom:5px; padding:10px;'>";
			$html[] = "<summary style='background-color:#384148; color:#fff; padding:10px;'>WT Amo CRM - JoomShopping debug information</summary>";
			$html[] = $debug_info;
			$html[] = '</details>';
			$session->clear("amocrmjshoppingdebugoutput");

			if (!empty($html)) {
				$buffer = preg_replace('/(<body.*>)/Ui', '$1' . implode('', $html), $buffer);
				$this->getApplication()->setBody($buffer);
			}
		}
	}

}//plgSystemWt_amocrm_jshopping
