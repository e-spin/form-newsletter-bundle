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

declare(strict_types=1);

/**
 * Front end form fields
 */

use Espin\FormNewsletterBundle\EventListener\FormFieldNewsletter;
use Espin\FormNewsletterBundle\EventListener\FormNewsletter;

$GLOBALS['TL_FFL']['newsletter'] = FormFieldNewsletter::class;

/**
 * Hooks
 */
//$GLOBALS['TL_HOOKS']['generatePage'][]    =
//    ['Espin\FormNewsletterBundle\EventListener\FormNewsletter', 'activateRecipient'];
//$GLOBALS['TL_HOOKS']['processFormData'][] =
//    ['Espin\FormNewsletterBundle\EventListener\FormNewsletter', 'processFormData'];

/**
 * Replace the notification_center hook to send form notification
 */
if (\array_key_exists('notification_center', \Contao\System::getContainer()->getParameter('kernel.bundles'))) {
    foreach ($GLOBALS['TL_HOOKS']['processFormData'] as $k => $v) {
        if ($v[0] == 'NotificationCenter\tl_form' && $v[1] == 'sendFormNotification') {
            unset($GLOBALS['TL_HOOKS']['processFormData'][$k]);
            $GLOBALS['TL_HOOKS']['processFormData'][] =
                [FormNewsletter::class, 'sendFormNotification'];
        }
    }

    $arrTokens = ['newsletter_token', 'newsletter_domain', 'newsletter_link', 'newsletter_channels'];

    foreach (['email_subject', 'email_text', 'email_html', 'file_name', 'file_content'] as $type) {
        $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['contao']['core_form'][$type] =
            array_merge($GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['contao']['core_form'][$type], $arrTokens);
    }
}
