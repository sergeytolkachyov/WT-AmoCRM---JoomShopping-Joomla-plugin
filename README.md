[![Version](https://img.shields.io/badge/Version-1.1.0-blue.svg)](https://web-tolk.ru/dev/joomla-plugins/wt-amocrm-joomshopping.html?utm_source=github) [![Status](https://img.shields.io/badge/Status-stable-green.svg)]() [![JoomlaVersion](https://img.shields.io/badge/Joomla-4.2-orange.svg)]() [![JoomShoppingVersion](https://img.shields.io/badge/JoomShopping-5.1.x-important.svg)]() [![DocumentationRus](https://img.shields.io/badge/Documentation-rus-blue.svg)](https://web-tolk.ru/dev/joomla-plugins/wt-amocrm-joomshopping.html?utm_source=github) [![DocumentationEng](https://img.shields.io/badge/Documentation-eng-blueviolet.svg)](https://web-tolk.ru/en/dev/joomla-plugins/wt-amocrm-joomshopping.html?utm_source=github)

# WT AmoCRM - JoomShopping Joomla plugin
Плагин отправки заказов из интернет-магазина JoomShopping в Amo CRM. Для работы плагина нужно установить и настроить библиотеку [WT Amo CRM library (GitHub)](https://github.com/sergeytolkachyov/WT-Amo-CRM-library-for-Joomla-4) [WT Amo CRM library (пакет для установки)](https://web-tolk.ru/dev/biblioteki/wt-amo-crm-library.html)
## Особенности плагина
- интеграция по REST API AmoCRM с помощью библиотеки WT Amocrm (необходимо установить для работы плагина)
- 37 полей JoomShopping
- неограниченное количество полей AmoCRM
- автоматическое создание сделки + контакта.
- гибкие настройки сопоставления полей Amo CRM и JoomShopping
- выбор воронки для создания сделки
- выбор тега для создания сделки
- обнаружение и передача UTM-меток в сделку
- 2 режима создания сделок: всегда и только при успешной оплате
- Возможность указать префикс для названия сделки в AmoCRM
- Список товаров заказа, комментарий покупателя к заказу и общая сумма заказа добавляются в примечание к сделке AmoCRM.
## Данные в примечании к сделке
Визуально интерфейс карточки сделки в AmoCRM похож на окно десктопного мессенджера, где большую часть экрана занимает "чат" сделки. Часть информации можно увидеть в полях сделки, которые можно создавать в любом количестве. Данный плагин передаёт список товаров заказа, комментарий покупателя к заказу и общую сумму заказа как примечание к сделке AmoCRM, так как менеджерам по продажам не удобно воспринимать  их в полях сделки. 
![image](https://user-images.githubusercontent.com/6236403/223925261-3246e979-597b-4c93-9a50-81a2f61c0bf7.png)
