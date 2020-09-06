<?php

/**
 * This file is part of e-spin/form-newsletter-bundle.
 *
 * Copyright (c) 2020 e-spin
 *
 * @package   e-spin/form-newsletter-bundle
 * @author    Ingolf Steinhardt <info@e-spin.de>
 * @author    Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @copyright 2020 e-spin
 * @license   LGPL-3.0-or-later
 */

/**
 * Form fields
 */
$GLOBALS['TL_LANG']['FFL']['newsletter'] = ['Newsletter subcsribe'];

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_email']        = ['E-mail field', 'Please choose the e-mail field.'];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_channels']     = ['Channels', 'Please select one or more channels.'];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_hideChannels'] =
    ['Hide channel menu', 'Do not show the channel selection menu.'];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_jumpTo']       = [
    'Confirmation page',
    'Here you can choose the confirmation page that will be displayed after user activates his subscription.'
];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_confirmation'] = [
    'Send confirmation e-mail separately',
    'Send the confirmation e-mail separately If you do not select this option, make sure you include newsletter tokens in the notification.'
];
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_subscribe']    = [
    'Subscription message',
    'You can use the wildcards &lt;em&gt;##channels##&lt;/em&gt; (channel names), &lt;em&gt;##domain##&lt;/em&gt; (domain name) and &lt;em&gt;##link##&lt;/em&gt; (activation link).'
];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_form_field']['newsletter_legend'] = 'Newsletter settings';
