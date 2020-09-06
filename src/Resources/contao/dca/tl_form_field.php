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
 * Add palettes to tl_form_field
 */

use Espin\FormNewsletterBundle\EventListener\FormNewsletter;

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][]             = 'newsletter_confirmation';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['newsletter']                 =
    '{type_legend},type,name,label;{fconfig_legend},mandatory,newsletter_email;{newsletter_legend},newsletter_channels,newsletter_hideChannels,newsletter_jumpTo,newsletter_confirmation;{expert_legend:hide},class;{template_legend:hide},customTpl;{submit_legend},addSubmit';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['newsletter_confirmation'] = 'newsletter_subscribe';

/**
 * Add fields to tl_form_field
 */
$GLOBALS['TL_DCA']['tl_form_field']['fields']['newsletter_email'] =
    [
        'label'            => &$GLOBALS['TL_LANG']['tl_form_field']['newsletter_email'],
        'exclude'          => true,
        'inputType'        => 'select',
        'options_callback' => [FormNewsletter::class, 'getEmailFields'],
        'eval'             => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
        'sql'              => "int(10) unsigned NOT NULL default '0'",
        'relation'         => ['type' => 'hasOne', 'load' => 'eager', 'table' => 'tl_form_field']
    ];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['newsletter_channels'] =
    [
        'label'      => &$GLOBALS['TL_LANG']['tl_form_field']['newsletter_channels'],
        'exclude'    => true,
        'inputType'  => 'checkbox',
        'foreignKey' => 'tl_newsletter_channel.title',
        'eval'       => ['mandatory' => true, 'multiple' => true],
        'sql'        => "blob NULL"
    ];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['newsletter_hideChannels'] =
    [
        'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['newsletter_hideChannels'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'sql'       => "char(1) NOT NULL default ''"
    ];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['newsletter_jumpTo'] =
    [
        'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['newsletter_jumpTo'],
        'exclude'   => true,
        'inputType' => 'pageTree',
        'eval'      => ['fieldType' => 'radio'],
        'sql'       => "int(10) unsigned NOT NULL default '0'"
    ];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['newsletter_confirmation'] =
    [
        'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['newsletter_confirmation'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['submitOnChange' => true],
        'sql'       => "char(1) NOT NULL default ''"
    ];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['newsletter_subscribe'] =
    [
        'label'         => &$GLOBALS['TL_LANG']['tl_form_field']['newsletter_subscribe'],
        'exclude'       => true,
        'inputType'     => 'textarea',
        'eval'          => ['style' => 'height:120px', 'decodeEntities' => true, 'alwaysSave' => true],
        'load_callback' =>
            [
                [FormNewsletter::class, 'getSubscribeDefault']
            ],
        'sql'           => "text NULL"
    ];
