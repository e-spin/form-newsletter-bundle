<?php

/**
 * This file is part of e-spin/form-newsletter-bundle.
 *
 * Copyright (c) 2020-2024 e-spin
 *
 * @package   e-spin/form-newsletter-bundle
 * @author    Ingolf Steinhardt <info@e-spin.de>
 * @author    Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @copyright 2020-2024 e-spin
 * @license   LGPL-3.0-or-later
 */

/**
 * Form fields
 */
$GLOBALS['TL_LANG']['FFL']['newsletter'] = ['Newsletter abonnieren'];

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_email']        = ['E-Mail Feld', 'Bitte wählen Sie das Feld für die E-Mail.'];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_channels']     = ['Verteiler', 'Bitte wählen Sie einen oder mehrere Verteiler.'];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_hideChannels'] =
    ['Verteilermenü ausblenden', 'Das Menü zum Auswählen von Verteilern nicht anzeigen'];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_jumpTo']       = [
    'Weiterleitungsseite',
    'Bitte wählen Sie die Seite aus, zu der Besucher beim Anklicken eines Links oder Abschicken eines Formulars weitergeleitet werden.'
];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_confirmation'] = [
    'E-Mail für die Bestätigung separat senden',
    'Senden Sie die E-Mail für die Bestätigung separat. Wenn Sie diese Option nicht wählen, stellen Sie sicher, dass Sie den Newsletter-Token in die Benachrichtigung aufnehmen.'
];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_subscribe']    = [
    'Abonnementbestätigung',
    'Sie können die Platzhalter ##channels## (Name der Verteiler), ##domain## (Domainname) und ##link## (Aktivierungslink) verwenden.'
];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_legend'] = 'Newsletter Einstellungen';
