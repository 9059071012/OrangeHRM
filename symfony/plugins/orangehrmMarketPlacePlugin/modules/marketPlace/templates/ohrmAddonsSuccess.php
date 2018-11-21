<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301, USA
 */
use_stylesheet(plugin_web_path('orangehrmMarketPlacePlugin', 'css/ohrmAddonSuccess.css'));
use_javascript(plugin_web_path('orangehrmMarketPlacePlugin', 'js/ohrmAddonSuccess.js'));
?>
<div class="box">
    <div class="head">
        <h1 id="menu">OrangeHRM Addons</h1>
    </div>
    <div class="inner">
        <?php foreach ($addonList as $addon) { ?>
            <div class="row">
                <div class="inner container" id="addonHolder">
                    <button class="accordion" addOnId="<?php echo $addon['id']; ?>">
                        <div id="column" class="image">
                            <img class="circle" src="
                        <?php echo plugin_web_path("orangehrmMarketPlacePlugin", "images/45GlBEsi_400x400.jpeg"); ?>"/>
                        </div>
                        <div id="column" class="details">
                            <div class="row">
                                <label id="title"><?php echo __($addon['title']); ?></label>
                            </div>
                            <div class="row">
                                <p><?php echo __($addon['summary']); ?></p>
                            </div>
                        </div>
                        <div id="column" class="button">
                            <input type="button" name="Submit" class="<?php
                            $installedAddons = $sf_data->getRaw("installedAddons");
                            if (in_array($addon['id'], $installedAddons)) {
                                echo "delete";
                            } ?>" id="btn1" value="<?php
                            if (in_array($addon['id'], $installedAddons)) {
                                echo __("Uninstall");
                            } else {
                                echo __("Install");
                            } ?>"/>
                        </div>
                    </button>
                    <div class="panel">
                        <p>Lorem ipsum...</p>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<script>
    var ajaxUrl = "<?php echo url_for('marketPlace/getAddonDescriptionAPI'); ?>";
</script>

