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

use Contao\FormCheckbox;
use Contao\NewsletterChannelModel;
use Contao\StringUtil;

/**
 * Class FormFieldNewsletter
 *
 * Front end form field "newsletter".
 */
class FormFieldNewsletter extends FormCheckbox
{
    /**
     * Prepare the options
     *
     * @param array $arrAttributes
     */
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        // Hide the channels
        if ($this->newsletter_hideChannels) {
            $this->arrOptions =
                [
                    [
                        'value' => 1,
                        'label' => $this->strLabel
                    ]
                ];

            $this->strLabel = '';
        } else {
            // Generate the channels as options.
            $objChannels = NewsletterChannelModel::findByIds(StringUtil::deserialize($this->newsletter_channels, true));

            if ($objChannels !== null) {
                $arrOptions = [];

                while ($objChannels->next()) {
                    $arrOptions[] =
                        [
                            'value' => $objChannels->id,
                            'label' => $objChannels->title
                        ];
                }

                $this->arrOptions = $arrOptions;
                $this->multiple   = true;
            }
        }
    }
}
