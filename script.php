<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;

/**
 * Script file of HelloWorld component.
 *
 * The name of this class is dependent on the component being installed.
 * The class name should have the component's name, directly followed by
 * the text InstallerScript (ex:. com_helloWorldInstallerScript).
 *
 * This class will be called by Joomla!'s installer, if specified in your component's
 * manifest file, and is used for custom automation actions in its installation process.
 *
 * In order to use this automation script, you should reference it in your component's
 * manifest file as follows:
 * <scriptfile>script.php</scriptfile>
 *
 * @package     Joomla.Administrator
 * @subpackage  com_helloworld
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
class plgSystemWt_amocrm_jshoppingInstallerScript
{
    /**
     * This method is called after a component is installed.
     *
     * @param  \stdClass $installer - Parent object calling this method.
     *
     * @return void
     */
    public function install($installer)
    {
		
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
			$query->select($db->quoteName(array('extension_id', 'enabled')))
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('element') . ' = ' . $db->quote('Webtolk/Amocrm'));
		$result = $db->setQuery($query)->loadObject();
		
		if(isset($result->extension_id) && !empty($result->extension_id))
		{
			return;
		}
		
		$wt_amo_crm_lib_url =  'https://web-tolk.ru/get.html?element=pkg_lib_wt_amocrm';

		if (!$this->installDependencies($installer, $$wt_amo_crm_lib_url))
		{

			$app->enqueueMessage(
				Text::sprintf('WT AmoCRM library is not installed or updated',
					Text::_('Cannot install or update the WT AmoCRM library. PLease, <a href="' . $$wt_amo_crm_lib_url . '" class="btn btn-small btn-primary">download</a> it and install/update manually.')
				), 'error'
			);

		}
    }

    /**
     * This method is called after a component is uninstalled.
     *
     * @param  \stdClass $installer - Parent object calling this method.
     *
     * @return void
     */
    public function uninstall($installer) 
    {

		
    }

    /**
     * This method is called after a component is updated.
     *
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    public function update($installer) 
    {

    }

    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param  string    $type   - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    public function preflight($type, $installer) 
    {
	    $version = new Version;

	    // only for Joomla 3.x

	    if (version_compare($version->getShortVersion(), '4.0', '<')) {

		    Factory::getApplication()->enqueueMessage('&#128546; <strong>WT AmoCRM - JoomShopping</strong> plugin doesn\'t support Joomla versions <span class="alert-link">lower 4</span>. Your Joomla version is <span class="badge badge-important">'.$version->getShortVersion().'</span>','error');
		    return false;

	    }
    }
	


    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param  string    $type   - Type of PostFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    function postflight($type, $installer)
    {
	    $smile = '';
	    if($type != 'uninstall')
	    {
		    $smiles    = ['&#9786;', '&#128512;', '&#128521;', '&#128525;', '&#128526;', '&#128522;', '&#128591;'];
		    $smile_key = array_rand($smiles, 1);
		    $smile     = $smiles[$smile_key];
	    }

	    $element = strtoupper($installer->getElement());
		echo "
		<style>	.thirdpartyintegration {
				display:flex;
				padding: 3px 5px;
				align-items:center;
			}
			.thirdpartyintegration-logo {
				height:32px;
				float:left; 
				margin-right: 5px;
			}
			
			.thirdpartyintegration.success {
				border: 1px solid #2F6F2F;
				background-color:#dfffdf;
			}
			.thirdpartyintegration.error {
				border: 1px solid #bd362f;
				background-color:#ffdddb;
			}
		</style>
		<div class='row bg-white m-3 p-3 shadow-sm border'>
		<div class='col-12 col-lg-8'>
		<h2>".$smile." ".Text::_("PLG_".$element."_AFTER_".strtoupper($type))." <br/>".Text::_("PLG_".$element)."</h2>
		".Text::_("PLG_".$element."_DESC");
		
		
			echo Text::_("PLG_".$element."_WHATS_NEW");


		    $thirdpartyextensions="";
		    if(file_exists(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml")){
			    $nevigen_quick_order = simplexml_load_file(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml");
			    if($nevigen_quick_order->author == "Nevigen.com" && $nevigen_quick_order->authorUrl == "https://nevigen.com/"){

				    $thirdpartyextensions .=  "<div class='thirdpartyintegration success'><img class='thirdpartyintegration-logo' src='https://nevigen.com/images/corpstyle/logo.png'/>
								<div class='media-body'><strong>".$nevigen_quick_order->author."'s</strong> plugin <strong>".$nevigen_quick_order->name." v.".$nevigen_quick_order->version."</strong> detected. <a href='".$nevigen_quick_order->authorUrl."' target='_blank'>".$nevigen_quick_order->authorUrl."</a> <a href='mailto:".$nevigen_quick_order->authorEmail."' target='_blank'>".$nevigen_quick_order->authorEmail."</a></div>
							</div>";
			    }
		    }


		echo "</div>
		<div class='col-12 col-lg-4 d-flex flex-column justify-content-start'>
		<img width='200px' src='https://web-tolk.ru/web_tolk_logo_wide.png'>
		<p>Joomla Extensions</p>
		<p class='btn-group'>
			<a class='btn btn-sm btn-outline-primary' href='https://web-tolk.ru' target='_blank'>https://web-tolk.ru</a>
			<a class='btn btn-sm btn-outline-primary' href='mailto:info@web-tolk.ru'><i class='icon-envelope'></i> info@web-tolk.ru</a>
		</p>
		<p><a class='btn btn-info' href='https://t.me/joomlaru' target='_blank'>Joomla Russian Community in Telegram</a></p>
		
		".Text::_("PLG_".$element."_MAYBE_INTERESTING")."
		</div>


		";		
	
    }
	
	/**
	 * @param $parent
	 *
	 * @throws Exception
	 *
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	protected function installDependencies($parent, $url)
	{
		// Load installer plugins for assistance if required:
		PluginHelper::importPlugin('installer');

		$app = Factory::getApplication();

		$package = null;

		// This event allows an input pre-treatment, a custom pre-packing or custom installation.
		// (e.g. from a JSON description).
		$results = $app->triggerEvent('onInstallerBeforeInstallation', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			return false;
		}


		// Download the package at the URL given.
		$p_file = InstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

			return false;
		}

		$config   = Factory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file.
		$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		// This event allows a custom installation of the package or a customization of the package:
		$results = $app->triggerEvent('onInstallerBeforeInstaller', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			return false;
		}

		// Get an installer instance.
		$installer = new Installer();

		/*
		 * Check for a Joomla core package.
		 * To do this we need to set the source path to find the manifest (the same first step as JInstaller::install())
		 *
		 * This must be done before the unpacked check because JInstallerHelper::detectType() returns a boolean false since the manifest
		 * can't be found in the expected location.
		 */
		if (is_array($package) && isset($package['dir']) && is_dir($package['dir']))
		{
			$installer->setPath('source', $package['dir']);

			if (!$installer->findManifest())
			{
				InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
				$app->enqueueMessage(Text::sprintf('COM_INSTALLER_INSTALL_ERROR', '.'), 'warning');

				return false;
			}
		}

		// Was the package unpacked?
		if (!$package || !$package['type'])
		{
			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			$app->enqueueMessage(Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

			return false;
		}

		// Install the package.
		if (!$installer->install($package['dir']))
		{
			// There was an error installing the package.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_ERROR',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = false;
			$msgType = 'error';
		}
		else
		{
			// Package installed successfully.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = true;
			$msgType = 'message';
		}

		// This event allows a custom a post-flight:
		$app->triggerEvent('onInstallerAfterInstaller', array($parent, &$package, $installer, &$result, &$msg));

		$app->enqueueMessage($msg, $msgType);

		// Cleanup the install files.
		if (!is_file($package['packagefile']))
		{
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}

		InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		return $result;
	}
	
}