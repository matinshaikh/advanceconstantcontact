<?php
/**
 * AdvanceConstantContact plugin for Craft CMS 3.x
 *
 * Basic integration with Constant Contact API to allow you to add new contacts to your Constant Contact lists.
 *
 * @link      http://qaswaweb.com/
 * @copyright Copyright (c) 2019 Matin Shaikh
 */

namespace matinshaikh\advanceconstantcontact\models;

use craft\base\Model;

class Settings extends Model {
    public $key = '';
    public $token = '';
    public $list = '';
}