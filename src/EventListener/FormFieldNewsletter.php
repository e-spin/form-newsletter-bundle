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

use Contao\FormCheckBox;
use Contao\NewsletterChannelModel;

/**
 * Class FormFieldNewsletter
 *
 * Front end form field "newsletter".
 */
class FormFieldNewsletter extends FormCheckBox
{
    /**
     * Prepare the options
     *
     * @param array
     */
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        // Check if channels set.
        if(null === $this->newsletter_channels) {
            return $this->arrOptions = [];
        }

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
            // Generate the channels as options
            $objChannels = NewsletterChannelModel::findByIds(\StringUtil::deserialize($this->newsletter_channels, true));

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
            }
        }
    }
}
