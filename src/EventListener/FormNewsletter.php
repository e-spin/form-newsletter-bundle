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

namespace Espin\FormNewsletterBundle\EventListener;

use Contao\Database;
use Contao\DataContainer;
use Contao\Email;
use Contao\Environment;
use Contao\FormFieldModel;
use Contao\Idna;
use Contao\Input;
use Contao\NewsletterChannelModel;
use Contao\NewsletterRecipientsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use NotificationCenter\Model\Notification;

/**
 * Class FormNewsletter
 *
 * Provide methods to handle form newsletter subscription.
 */
class FormNewsletter
{
    /**
     * Tokens
     *
     * @var array
     */
    public static $arrTokens = [];

    /**
     * Activate the newsletter recipient
     */
    public function activateRecipient()
    {
        if (!Input::get('form_newsletter_token')) {
            return;
        }

        $objRecipient = NewsletterRecipientsModel::findByToken(Input::get('form_newsletter_token'));

        if ($objRecipient === null) {
            return;
        }

        $time        = time();
        $arrAdd      = [];
        $arrChannels = [];
        $arrCids     = [];
        $intJumpTo   = 0;

        // Update the subscriptions
        while ($objRecipient->next()) {
            $objChannel = $objRecipient->getRelated('pid');

            $arrAdd[]      = $objRecipient->id;
            $arrChannels[] = $objChannel->title;
            $arrCids[]     = $objChannel->id;

            $objRecipient->active    = 1;
            $objRecipient->token     = '';
            $objRecipient->pid       = $objChannel->id;
            $objRecipient->confirmed = $time;
            $objRecipient->save();

            // Set the jumpTo page
            if ($objRecipient->form_newsletter_jumpTo) {
                $intJumpTo = $objRecipient->form_newsletter_jumpTo;
            }
        }

        // Log activity
        System::log(
            $objRecipient->email . ' has subscribed to the following channels: ' . implode(', ', $arrChannels),
            __METHOD__,
            TL_NEWSLETTER
        );

        // HOOK: post activation callback
        if (isset($GLOBALS['TL_HOOKS']['activateRecipient']) && is_array($GLOBALS['TL_HOOKS']['activateRecipient'])) {
            foreach ($GLOBALS['TL_HOOKS']['activateRecipient'] as $callback) {
                System::importStatic($callback[0])->$callback[1]($objRecipient->email, $arrAdd, $arrCids);
            }
        }

        // Redirect to the confirmation page
        if ($intJumpTo > 0) {
            $objJump = PageModel::findByPk($intJumpTo);

            if ($objJump !== null) {
                System::redirect($objJump->getFrontendUrl());
            }
        }

        System::redirect(
            str_replace(
                '?form_newsletter_token=' . Input::get('form_newsletter_token'),
                '',
                Environment::get('request')
            )
        );
    }

    /**
     * Process the form data
     *
     * @param array
     * @param array
     */
    public function processFormData($arrData, $arrForm)
    {
        $objFields = FormFieldModel::findPublishedByPid($arrForm['id']);

        if ($objFields === null) {
            return;
        }

        $arrSubscriptions = [];

        // Collect the channels to subscribe
        while ($objFields->next()) {

            // Skip the non newsletter fields
            if ($objFields->type != 'newsletter') {
                continue;
            }

            // No data
            if (!$_SESSION['FORM_DATA'][$objFields->name]) {
                continue;
            }

            $strEmail = $_SESSION['FORM_DATA'][$objFields->current()->getRelated('newsletter_email')->name];

            // No e-mail address
            if (!Validator::isEmail($strEmail)) {
                continue;
            }

            if (!isset($arrSubscriptions[$strEmail])) {
                $arrSubscriptions[$strEmail] =
                    [
                        'channels'     => [],
                        'confirmation' => '',
                        'jumpTo'       => 0
                    ];
            }

            $arrChannels = \StringUtil::deserialize($objFields->newsletter_channels, true);

            // Store only those channels that were chosen
            if (!$objFields->newsletter_hideChannels) {
                $arrChannels = array_intersect($arrChannels, (array) $_SESSION['FORM_DATA'][$objFields->name]);

                if (empty($arrChannels)) {
                    continue;
                }
            }

            $arrSubscriptions[$strEmail]['channels'] =
                array_merge($arrSubscriptions[$strEmail]['channels'], $arrChannels);

            // Set the confirmation text
            if ($objFields->newsletter_confirmation) {
                $arrSubscriptions[$strEmail]['confirmation'] = $objFields->newsletter_subscribe;
            }

            // Set the jumpTo page
            if ($objFields->newsletter_jumpTo) {
                $arrSubscriptions[$strEmail]['jumpTo'] = $objFields->newsletter_jumpTo;
            }
        }

        // Nothing to subscribe
        if (empty($arrSubscriptions)) {
            return;
        }

        $time     = time();
        $strToken = md5(uniqid(mt_rand(), true));

        foreach ($arrSubscriptions as $strEmail => $arrSubscription) {
            $objChannels = NewsletterChannelModel::findByIds(array_unique($arrSubscription['channels']));

            // There are no channels
            if ($objChannels === null) {
                continue;
            }

            $subscriptions = [];

            // Get the existing active subscriptions
            if (($objSubscription = NewsletterRecipientsModel::findBy(["email=? AND active=1"], $strEmail))
                !== null) {
                $subscriptions = $objSubscription->fetchEach('pid');
            }

            $arrNew = array_diff($objChannels->fetchEach('id'), $subscriptions);

            // Continue if there are no new subscriptions
            if (!is_array($arrNew) || empty($arrNew)) {
                continue;
            }

            // Remove old subscriptions that have not been activated yet
            if (($objOld = NewsletterRecipientsModel::findBy(["email=? AND active=''"], $strEmail)) !== null) {
                while ($objOld->next()) {
                    $objOld->delete();
                }
            }

            // Add the new subscriptions
            foreach ($arrNew as $id) {
                $objRecipient = new NewsletterRecipientsModel();

                $objRecipient->pid                    = $id;
                $objRecipient->tstamp                 = $time;
                $objRecipient->email                  = $strEmail;
                $objRecipient->active                 = '';
                $objRecipient->addedOn                = $time;
                $objRecipient->ip                     = System::anonymizeIp(Environment::get('ip'));
                $objRecipient->token                  = $strToken;
                $objRecipient->confirmed              = '';
                $objRecipient->form_newsletter_jumpTo = $arrSubscription['jumpTo'];

                $objRecipient->save();
            }

            $strChannels = implode("\n", $objChannels->fetchEach('title'));

            $arrTokens =
                [
                    'token'    => $strToken,
                    'domain'   => Idna::decode(Environment::get('host')),
                    'link'     => Idna::decode(Environment::get('base')) . '?form_newsletter_token=' . $strToken,
                    'channel'  => $strChannels,
                    'channels' => $strChannels,
                ];

            // Set the tokens if they do not exist or just add channels
            if (empty(static::$arrTokens)) {
                static::$arrTokens = $arrTokens;
            } else {
                $arrTokens['channel']  .= $strChannels . "\n";
                $arrTokens['channels'] .= $strChannels . "\n";
            }

            // Send the separate e-mail confirmation
            if ($arrSubscription['confirmation']) {
                $objEmail           = new Email();
                $objEmail->from     = $GLOBALS['TL_ADMIN_EMAIL'];
                $objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
                $objEmail->subject  =
                    sprintf($GLOBALS['TL_LANG']['MSC']['nl_subject'], Idna::decode(Environment::get('host')));
                $objEmail->text     = StringUtil::parseSimpleTokens($arrSubscription['confirmation'], $arrTokens);
                $objEmail->sendTo($strEmail);
            }
        }
    }

    /**
     * Send the form notification
     *
     * @param $arrData
     * @param $arrForm
     * @param $arrFiles
     * @param $arrLabels
     */
    public function sendFormNotification($arrData, $arrForm, $arrFiles, $arrLabels)
    {
        $objHelper = System::importStatic('\NotificationCenter\tl_form');

        // Use the original method to send notification
        if (empty(static::$arrTokens)) {
            $objHelper->sendFormNotification($arrData, $arrForm, $arrFiles, $arrLabels);
            return;
        }

        if (!$arrForm['nc_notification']
            || ($objNotification = Notification::findByPk($arrForm['nc_notification']))
               === null) {
            return;
        }

        $arrTokens = [];

        foreach ($arrData as $k => $v) {
            $objHelper->flatten($v, 'form_' . $k, $arrTokens);
        }

        foreach ($arrForm as $k => $v) {
            $objHelper->flatten($v, 'formconfig_' . $k, $arrTokens);
        }

        // Add the newsletter tokens
        foreach (static::$arrTokens as $k => $v) {
            $arrTokens['newsletter_' . $k] = $v;
        }

        // Administrator e-mail
        $arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        $objNotification->send($arrTokens, $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * Get the e-mail form fields
     *
     * @param DataContainer
     */
    public function getEmailFields(DataContainer $dc)
    {
        $arrFields = [];
        $objFields = Database::getInstance()->prepare(
            "SELECT * FROM tl_form_field WHERE name!='' AND id!=? AND pid=(SELECT pid FROM tl_form_field WHERE id=?) ORDER BY sorting"
        )
            ->execute($dc->id, $dc->id);

        while ($objFields->next()) {
            $arrFields[$objFields->id] =
                ($objFields->label ? ($objFields->label . ' ') : '') . ' [' . $objFields->name . ']';
        }

        return $arrFields;
    }

    /**
     * Load the default subscribe text
     *
     * @param mixed
     *
     * @return mixed
     */
    public function getSubscribeDefault($varValue)
    {
        if(null === $varValue) {
            return;
        }

        if (!trim($varValue)) {
            System::loadLanguageFile('tl_module');
            $varValue = trim($GLOBALS['TL_LANG']['tl_module']['text_subscribe'][1]);
        }

        return $varValue;
    }
}
