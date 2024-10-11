# Form newsletter bundle

## English
With this extension you can add a newsletter subscription to a form in the Contao CMS.

A new form widget is available, which allows the setting options such as the FE module
such as the selection of subscriptions or whether the confirmation should be sent by a separate email.

> [!TIP] From version 2.1

The registration information is also included in the POST data of the form and is available in the HOOKs
`prepareFormData` and `processFormData`.

If the information is to be transmitted via the ‘[Notification Centre](https://github.com/terminal42/contao-notification_center)’
the following tokens can be used:

- ##form_newsletter_token##
- ##form_newsletter_domain##
- ##form_newsletter_link##
- ##form_newsletter_channels##
- ##form_newsletter_channels_inline##

## Deutsch
Mit dieser Erweiterung können Sie eine Newsletter-Anmeldung zu einem Formular im Contao CMS hinzufügen.

Es steht ein neues Formularwidget zur Verfügung, welches die Einstellungsmöglichkeiten wie das FE-Modul
zur Verfügung stellt - wie z. B. die Auswahl der Abonnements oder ob die Bestätigung über eine separate E-Mail
erfolgen soll.

> [!TIP] Ab Version 2.1

Die Informationen zur Anmeldung werden auch in die POST-Daten des Formulars eingeschleust und stehen in den HOOKs
`prepareFormData` und `processFormData` zur Verfügung.

Sollen die Informationen über das "[Notification-Center](https://github.com/terminal42/contao-notification_center)"
übermittelt werden, können die folgenden Tokens verwendet werden:

- ##form_newsletter_token##
- ##form_newsletter_domain##
- ##form_newsletter_link##
- ##form_newsletter_channels##
- ##form_newsletter_channels_inline##

![Screenshot widget](https://github.com/e-spin/form-newsletter-bundle/blob/master/doc/screenshot_01.png?raw=true "Screenshot widget")
