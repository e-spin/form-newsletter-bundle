<?php

/**
 * form_newsletter extension for Contao Open Source CMS
 *
 * Copyright (C) 2014 e-spin
 *
 * @package form_newsletter
 * @author  Codefog <http://codefog.pl>
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license Commercial
 */

/**
 * Add fields to tl_newsletter_recipients
 */
$GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['form_newsletter_jumpTo'] = array
(
    'sql' => "int(10) unsigned NOT NULL default '0'",
);
