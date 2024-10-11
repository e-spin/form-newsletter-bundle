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

declare(strict_types=1);

namespace Espin\FormNewsletterBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\DataContainer;
use Contao\Database;
use Contao\Email;
use Contao\Environment;
use Contao\Form;
use Contao\Idna;
use Contao\Input;
use Contao\NewsletterChannelModel;
use Contao\NewsletterDenyListModel;
use Contao\NewsletterRecipientsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Contao\CoreBundle\OptIn\OptIn;
use NotificationCenter\Model\Notification;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
     * @var array $arrTokens
     */
    public static array $arrTokens = [];

    /**
     * Token name
     *
     * @var string
     */
    private string $tokenName = 'nl_token';

    /**
     * SimpleTokenParser
     *
     * @var SimpleTokenParser $parser
     */
    private SimpleTokenParser $parser;

    /**
     * OptIn
     *
     * @var OptIn
     */
    private OptIn $optIn;

    /**
     * The logger to use.
     *
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * Create a new instance.
     *
     * @param SimpleTokenParser    $parser
     * @param OptIn                $optIn
     * @param LoggerInterface|null $logger
     */
    public function __construct(SimpleTokenParser $parser, OptIn $optIn, ?LoggerInterface $logger)
    {
        $this->parser = $parser;
        $this->optIn  = $optIn;
        $this->logger = $logger;
    }

    /**
     * Activate the newsletter recipient
     */
    public function onActivateRecipient(): void
    {
        if (!Input::get($this->tokenName)) {
            return;
        }

        // Find an unconfirmed token.
        if ((!$optInToken = $this->optIn->find(Input::get($this->tokenName)))
            || !$optInToken->isValid()
            || \count($arrRelated = $optInToken->getRelatedRecords()) < 1
            || \key($arrRelated) !== 'tl_newsletter_recipients'
            || \count($arrIds = current($arrRelated)) < 1) {
            return;
        }

        if ($optInToken->isConfirmed()) {
            return;
        }

        $arrRecipients = [];

        // Validate the token
        foreach ($arrIds as $intId) {
            if (!$objRecipient = NewsletterRecipientsModel::findByPk($intId)) {
                return;
            }

            if ($optInToken->getEmail() !== $objRecipient->email) {
                return;
            }

            $arrRecipients[] = $objRecipient;
        }

        $time      = \time();
        $arrAdd    = [];
        $arrCids   = [];
        $channels  = [];
        $intJumpTo = 0;

        // Activate the subscriptions
        foreach ($arrRecipients as $objRecipient) {
            $arrAdd[]  = $objRecipient->id;
            $arrCids[] = $objRecipient->pid;

            if (null !== ($channelName = NewsletterChannelModel::findById($objRecipient->pid)->title)) {
                $channels[] = $channelName;
            }

            $objRecipient->tstamp = $time;
            $objRecipient->active = true;
            $objRecipient->save();

            // Set the jumpTo page
            if ($objRecipient->form_newsletter_jumpTo) {
                $intJumpTo = $objRecipient->form_newsletter_jumpTo;
            }
        }

        $optInToken->confirm();

        // Log activity.
        $this->logger?->log(
            LogLevel::INFO,
            $optInToken->getEmail() . ' has activated to the following channels: ' . \implode(', ', $channels)
        );

        // Redirect to the confirmation page.
        if ($intJumpTo) {
            $objJump = PageModel::findByPk($intJumpTo);

            if ($objJump !== null) {
                Controller::redirect($objJump->getFrontendUrl());
            }
        }

        // Delete token.
        Controller::redirect(
            ($url = \str_replace(
                '?' . $this->tokenName . '=' . Input::get($this->tokenName),
                '',
                Environment::get('request')
            )) === '' ? '/' : $url
        );
    }

    /**
     * Prepare the form data.
     *
     * @param array $submittedData
     * @param array $labels
     * @param array $fields
     * @param Form  $form
     *
     * @throws \Exception
     */
    public function onPrepareFormData(
        array &$submittedData,
        array $labels,
        array $fields,
        Form $form
    ): void {
        $arrSubscriptions = [];

        // Collect the channels to subscribe.
        foreach ($fields as $field) {
            // Skip the non newsletter fields.
            if ($field->type !== 'newsletter') {
                continue;
            }

            // No data: continue.
            if (empty($submittedData[$field->name])) {
                continue;
            }

            $strEmail = $submittedData[$field->current()->getRelated('newsletter_email')->name];

            // No e-mail address: continue.
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

            $arrChannels = StringUtil::deserialize($field->newsletter_channels, true);

            // Store only those channels that were chosen.
            if (!$field->newsletter_hideChannels) {
                $arrChannels = \array_intersect($arrChannels, (array) $submittedData[$field->name]);
                if (empty($arrChannels)) {
                    continue;
                }
            }

            $arrSubscriptions[$strEmail]['channels'] =
                \array_merge($arrSubscriptions[$strEmail]['channels'], $arrChannels);

            // Set the confirmation text
            if ($field->newsletter_confirmation) {
                $arrSubscriptions[$strEmail]['confirmation'] = $field->newsletter_subscribe;
            }

            // Set the jumpTo page.
            if ($field->newsletter_jumpTo) {
                $arrSubscriptions[$strEmail]['jumpTo'] = $field->newsletter_jumpTo;
            }
        }

        // Nothing to subscribe
        if (empty($arrSubscriptions)) {
            return;
        }

        foreach ($arrSubscriptions as $strEmail => $arrSubscription) {
            $objChannels = NewsletterChannelModel::findByIds(\array_unique($arrSubscription['channels']));

            // There are no channels.
            if ($objChannels === null) {
                continue;
            }

            $subscriptions = [];

            // Get the existing active subscriptions.
            if (($objSubscription = NewsletterRecipientsModel::findBy(["email=? AND active=1"], $strEmail)) !== null) {
                $subscriptions = $objSubscription->fetchEach('pid');
            }

            $arrNew = \array_diff($objChannels->fetchEach('id'), $subscriptions);

            // Continue if there are no new subscriptions.
            if (!\is_array($arrNew) || empty($arrNew)) {
                continue;
            }

            // Remove old subscriptions that have not been activated yet.
            if (($objOld = NewsletterRecipientsModel::findOldSubscriptionsByEmailAndPids($strEmail, $arrNew))
                !== null) {
                while ($objOld->next()) {
                    $objOld->delete();
                }
            }

            $time       = \time();
            $arrRelated = [];

            // Add the new subscriptions.
            foreach ($arrNew as $id) {
                $objRecipient = new NewsletterRecipientsModel();

                $objRecipient->pid                    = $id;
                $objRecipient->tstamp                 = $time;
                $objRecipient->email                  = $strEmail;
                $objRecipient->active                 = 0;
                $objRecipient->addedOn                = $time;
                $objRecipient->form_newsletter_jumpTo = $arrSubscription['jumpTo'];
                $objRecipient->save();

                // Remove the deny list entry (see #4999).
                if (($objDenyList = NewsletterDenyListModel::findByHashAndPid(md5($strEmail), $id)) !== null) {
                    $objDenyList->delete();
                }

                $arrRelated['tl_newsletter_recipients'][] = $objRecipient->id;
            }

            // Get token.
            $optInToken = $this->optIn->create('nl', $strEmail, $arrRelated);

            // Create channels as text.
            $strChannels    = \implode("\n", $objChannels->fetchEach('title'));
            $channelsInLine = \implode(', ', $objChannels->fetchEach('title'));

            $arrTokens =
                [
                    'token'           => $optInToken->getIdentifier(),
                    'domain'          => Idna::decode(Environment::get('host')),
                    'link'            => Idna::decode(Environment::get('base')) . '?' . $this->tokenName . '='
                                         . $optInToken->getIdentifier(),
                    'channels'        => $strChannels,
                    'channels_inline' => $channelsInLine,
                ];

            // Set the tokens if they do not exist or just add channels.
            if (empty(static::$arrTokens)) {
                static::$arrTokens = $arrTokens;
            } else {
                $arrTokens['channels']        .= $strChannels . "\n";
                $arrTokens['channels_inline'] .= ', ' . $strChannels;
            }

            // Send the separate email confirmation.
            if ($arrSubscription['confirmation']) {
                $objEmail           = new Email();
                $objEmail->from     = $GLOBALS['TL_ADMIN_EMAIL'] ?? '';
                $objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? '';
                $objEmail->subject  =
                    \sprintf($GLOBALS['TL_LANG']['MSC']['nl_subject'], Idna::decode(Environment::get('host')));
                $objEmail->text     = $this->parser->parse($arrSubscription['confirmation'], $arrTokens);
                $objEmail->sendTo($strEmail);

                // Log activity.
                $this->logger?->log(
                    LogLevel::INFO,
                    $strEmail . ' has subscribed to the following channels: ' . $channelsInLine
                );
            }
        }

        // Add separate elements for output in emails.
        if (!empty(static::$arrTokens)) {
            foreach (static::$arrTokens as $key => $arrToken) {
                $submittedData['newsletter_' . $key] = $arrToken;
            }
        }
    }

    /**
     * Get the email form fields.
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getEmailFields(DataContainer $dc): array
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
     * Load the default subscribe text.
     *
     * @param mixed $varValue
     *
     * @return mixed
     */
    public function getSubscribeDefault(mixed $varValue): mixed
    {
        if (null === $varValue) {
            return null;
        }

        if (!\trim($varValue)) {
            System::loadLanguageFile('tl_module');
            $varValue = \trim($GLOBALS['TL_LANG']['tl_module']['text_subscribe'][1]);
        }

        return $varValue;
    }
}
