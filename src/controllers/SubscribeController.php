<?php
/**
 * AdvanceConstantContact plugin for Craft CMS 3.x
 *
 * Basic integration with Constant Contact API to allow you to add new contacts to your Constant Contact lists.
 *
 * @link      http://qaswaweb.com/
 * @copyright Copyright (c) 2019 Matin Shaikh
 */

namespace matinshaikh\advanceconstantcontact\controllers;

use matinshaikh\advanceconstantcontact\AdvanceConstantContact as Plugin;

use Craft;
use craft\web\Controller;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Matin Shaikh
 * @package   AdvanceConstantContact
 * @since     1.0.0
 */
class SubscribeController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

  
    /**
     * Handle a request going to our plugin's actionSubscribe URL,
     * e.g.: actions/constant-contact/subscribe
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $this->requirePostRequest();
        $plugin = Plugin::getInstance();
        $request = Craft::$app->getRequest();
        $settings = $plugin->getSettings();
        $email = $request->getParam('email');
        $firstName = $request->getParam('first_name');
        $lastName = $request->getParam('last_name');
        $companyName = $request->getParam('company_name');
        $listID = $request->getParam('listid', $settings->list); 
        $redirect = $request->getParam('redirect', '');
        $plugin = Plugin::getInstance();

        if ($email === '' || !$this->validateEmail($email)) {
             return [
                'success' => false,
                'message' => "Email address is invalid. Please try again."
            ];
        }

        $result = $plugin->constantContactService->subscribe($email, $listID, $firstName, $lastName,$companyName);

        if ($request->getAcceptsJson()) {
            return $this->asJson($result);
        }

        if ( $result['success'] == false) {
            Craft::$app->getSession()->setError($result['message']);
            return null;
        }

        if (  $result['success'] == true) {
            Craft::$app->getSession()->setNotice($result['message']);
            if ($redirect !== '') {
                return $this->redirectToPostedUrl();
            }
        }
        
        return null;
    }

    /**
     * Validate an email address.
     * Provide email address (raw input)
     * Returns true if the email address has the email
     * address format and the domain exists.
     *
     * @param string $email Email to validate
     *
     * @return boolean
     * @author André Elvan
     */
    public function validateEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else {
                if ($domainLen < 1 || $domainLen > 255) {
                    // domain part length exceeded
                    $isValid = false;
                } else {
                    if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                        // local part starts or ends with '.'
                        $isValid = false;
                    } else {
                        if (preg_match('/\\.\\./', $local)) {
                            // local part has two consecutive dots
                            $isValid = false;
                        } else {
                            if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                                // character not valid in domain part
                                $isValid = false;
                            } else {
                                if (preg_match('/\\.\\./', $domain)) {
                                    // domain part has two consecutive dots
                                    $isValid = false;
                                } else {
                                    if
                                    (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                        str_replace("\\\\", "", $local))
                                    ) {
                                        // character not valid in local part unless
                                        // local part is quoted
                                        if (!preg_match('/^"(\\\\"|[^"])+"$/',
                                            str_replace("\\\\", "", $local))
                                        ) {
                                            $isValid = false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
    }
}
