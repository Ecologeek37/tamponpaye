<?php
/* Copyright (C) 2023 Vincent Coulon
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    tamponpaye/class/actions_tamponpaye.class.php
 * \ingroup tamponpaye
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsTamponpaye
 */
class ActionsTamponpaye
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the printUnderHeaderPDFline function : replacing the parent's function with the one below
	 *
	 * @param   array           	$parameters     Hook metadatas (context, etc...)
	 * @param   ModelePDFFactures	$object         The object to process
	 */
	public function printUnderHeaderPDFline($parameters, &$model)
	{
		global $conf, $user, $langs;

		if ($conf->global->TAMPON_PAYE_TYPE !== '0') {

			if (isset($parameters['object']) && get_class($parameters['object']) == "Facture" && $parameters['object']->paye === '1') {

				// config
				$useImage = $conf->global->TAMPON_PAYE_TYPE === '1';
				$posX = $conf->global->TAMPON_PAYE_POS_X;
				$posY = $conf->global->TAMPON_PAYE_POS_Y;
				$rota = $conf->global->TAMPON_PAYE_ROTA;
				$scale = $conf->global->TAMPON_PAYE_SCALE;
				$alpha = $conf->global->TAMPON_PAYE_ALPHA;
				$file = $conf->global->TAMPON_PAYE_IMAGE;
				$text = $conf->global->TAMPON_PAYE_TEXT;
				$textColor = $conf->global->TAMPON_PAYE_TEXT_COLOR;

				// Start Transformation
				$parameters['pdf']->StartTransform();
				// Scale by $scale% centered by ($posX, $posY) which is the lower left corner of the rectangle
				$parameters['pdf']->ScaleXY($scale, $posX, $posY);
				// Rotate $rot degrees counter-clockwise centered by ($posX, $posY) which is the lower left corner of the rectangle
				$parameters['pdf']->Rotate($rota, $posX, $posY);
				// set alpha to semi-transparency
				$parameters['pdf']->SetAlpha($alpha);

				$logo = DOL_DOCUMENT_ROOT . $file;
				if (is_readable($logo) && $useImage) {
					$height = pdf_getHeightForLogo($logo);
					// Display image
					$parameters['pdf']->Image($logo, $posX, $posY, 0, $height); // width=0 (auto)
				} else {
					//display text
					list($r, $g, $b) = sscanf($textColor, "#%02x%02x%02x"); //hex to rgb
					// Store color
					$parameters['pdf']->SetTextColor($r, $g, $b);
					$parameters['pdf']->SetDrawColor($r, $g, $b);
					$parameters['pdf']->Rect($posX, $posY, 36, 17, 'D');
					$parameters['pdf']->SetFont('', 'B', 24);
					$parameters['pdf']->SetXY($posX + 2.3, $posY + 3);
					$parameters['pdf']->Write(0, $text, '', 0, '', true, 0, false, false, 0);
					// Set default colors
					$parameters['pdf']->SetTextColor(0, 0, 0);
					$parameters['pdf']->SetDrawColor(0, 0, 0);
				}
				// restore full opacity
				$parameters['pdf']->SetAlpha(1);
				// Stop Transformation
				$parameters['pdf']->StopTransform();
			}
		}
	}
}
