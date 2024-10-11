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

/**
 * Front end form fields
 */

use Espin\FormNewsletterBundle\EventListener\FormFieldNewsletter;

$GLOBALS['TL_FFL']['newsletter'] = FormFieldNewsletter::class;
