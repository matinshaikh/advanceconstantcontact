<?php
/**
 * AdvanceConstantContact plugin for Craft CMS 3.x
 *
 * Basic integration with Constant Contact API to allow you to add new contacts to your Constant Contact lists.
 *
 * @link      http://qaswaweb.com/
 * @copyright Copyright (c) 2019 Matin Shaikh
 */

namespace matinshaikh\advanceconstantcontact\services;

use matinshaikh\advanceconstantcontact\AdvanceConstantContact as Plugin;

use matinshaikh\advanceconstantcontact\lib\AdvanceConstantContact as Client;

use Craft;
use craft\base\Component;

/**
 * AdvanceConstantContactService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Matin Shaikh
 * @package   AdvanceConstantContact
 * @since     1.0.0
 */
class AdvanceConstantContactService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     AdvanceConstantContact::$plugin->advanceConstantContactService->exampleService()
     *
     * @return mixed
     */
    public function subscribe($email, $listID, $firstName = '', $lastName = '', $companyName='') {

        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();
        $client = new Client($settings->key, $settings->token);
        
        $options = [
            'query' => [
                'email' => $email
            ]
        ];

        try {
            $response = $client->request('GET', 'contacts', $options);
         } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'An unknown error occurred.'
            ];
        }

        $responseObj = json_decode($response->getBody()->getContents());
        if ( !empty($responseObj->results) ) {
            return $this->updateContact($responseObj->results, $listID);
        } else {
            return $this->addContact($email, $listID, $firstName, $lastName, $companyName);
        }

        return null;
    }

    /**
     *
     * @return mixed
     */
    private function addContact($email, $listID, $firstName, $lastName, $companyName) {
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();
        $client = new Client($settings->key, $settings->token);

        $payload = [
            'lists' => [
                ['id' => $listID]
            ],
            'email_addresses' => [
                ['email_address' => $email]
            ],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company_name' => $companyName

        ];

        try {
            $response = $client->addContact($payload, 'ACTION_BY_VISITOR');
            return [
                'success' => true,
                'message' => "You've been added to the list."
            ];
        } catch (\Exception $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);
            if ( !empty($error) ) { 
                return [
                    'success' => false,
                    'message' => 'Error: ' . $error[0]['error_message']
                ];
            }
            return [
                'success' => false,
                'message' => 'An unknown error occurred.'
            ];
        }
        return null;
    }

    /**
     *
     * @return mixed
     */
    private function updateContact($contact, $listID) {
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();
        $client = new Client($settings->key, $settings->token);

        $contact = reset($contact);
        $lists = $contact->lists;

        foreach ( $lists as $list ) {
            if ( $list->id == $listID ) {
                return [
                    'success' => false,
                    'message' => 'You\'re already subscribed to this list.'
                ];
            }
        }

        $contact->lists[] = (object) array('id'=>$listID,'status'=>'ACTIVE');

        try {
            $response = $client->updateContact($contact, 'ACTION_BY_VISITOR');
            return [
                'success' => true,
                'message' => "You've been added to the list."
            ];
        } catch (\Exception $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);
            if ( !empty($error) ) { 
                return [
                    'success' => false,
                    'message' => 'Error: ' . $error[0]['error_message']
                ];
            }
            return [
                'success' => false,
                'message' => 'An unknown error occurred.'
            ];
        }
        return null;
    }
}
