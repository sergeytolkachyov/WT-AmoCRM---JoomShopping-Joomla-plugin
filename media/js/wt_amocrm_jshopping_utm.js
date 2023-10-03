/**
 * @package     WT Amo CRM JoomShopping
 * @version     1.0.1
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2023 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0.0
 */
document.addEventListener('DOMContentLoaded', function () {
    let utms = [
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
    ];
    let plg_system_wt_amocrm_jshopping_version = Joomla.getOptions('plg_system_wt_amocrm_jshopping_version');
	console.group("WT Amocrm - JoomShopping v." + plg_system_wt_amocrm_jshopping_version + " Joomla plugin");
    
	utms.forEach(function(item){
	    try{
			const url = new URL(window.location.href);
			let utm = url.searchParams.get(item);
		
			console.log("From URL - " + item + " : " + utm);

            if(utm != null || utm !== ""){

                if (utm && (getCookie(item) == null || getCookie(item) === "")) {
					utm = encodeURIComponent(utm);
					document.cookie = encodeURIComponent(item) + '=' + encodeURIComponent(utm);
                }
            }
        } finally{
            return;
        }
    });
	console.groupEnd();
});

function getCookie(cname) {
	let name = cname + "=";
	// let decodedCookie = document.cookie;
	let ca = document.cookie.split(';');
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}