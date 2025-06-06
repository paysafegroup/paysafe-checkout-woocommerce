/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**********************************************!*\
  !*** ./resources/js/frontend/paysafecash.js ***!
  \**********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-registry */ "@woocommerce/blocks-registry");
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);






const paymentMethodId = "paysafecash";
const defaultLabel = 'Paysafe Checkout';
const settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__.getSetting)('paysafecash_data', {});
const log_paysafe_error = function (message, context) {
  if (settings.log_errors === true && settings.log_error_endpoint) {
    fetch(settings.log_error_endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: new Headers({
        // 'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Type': 'application/json'
      }),
      body: JSON.stringify({
        message: message,
        context: context
      })
    }).catch(error => {});
  }
};
const is_hosted_integration = settings.integration_type === 'paysafe_js';
const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.title) || defaultLabel;
const icons = [{
  id: 'paysafecash-icon',
  src: settings.icon,
  alt: label
}];
const Label = () => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    style: {
      width: '95%'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    style: {
      float: 'right'
    },
    src: settings.icon,
    alt: label
  }));
};
let paymentAmount = 0;
let currentCartData = {};
let next_page = null;
const show_checkout_error = function (error_message) {
  let woo_errors = document.getElementsByClassName('woocommerce-error');
  woo_errors = woo_errors.length > 0 ? woo_errors[0] : null;
  if (!woo_errors) {
    let notice_wrappers = document.getElementsByClassName('woocommerce-notices-wrapper');
    let notice_wrapper = notice_wrappers.length > 0 ? notice_wrappers[0] : null;
    if (notice_wrapper) {
      woo_errors = document.createElement('ul');
      woo_errors.className = 'woocommerce-error';
      notice_wrapper.appendChild(woo_errors);
    }
  }
  if (woo_errors) {
    let error_li = document.createElement('li');
    error_li.setAttribute('id', 'paysafe-fatal-error-message');
    error_li.innerHTML = error_message;
    woo_errors.appendChild(error_li);
    window.scrollTo({
      top: 0,
      left: 0,
      behavior: "smooth"
    });
  }
};
const Content = props => {
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onCheckoutSuccess
  } = eventRegistration;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
    const unsubscribe = onCheckoutSuccess(response => {
      const orderId = response.orderId;
      const customerSingleUseToken = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.single_use_token ? response.processingResponse.paymentDetails.single_use_token : '';
      const merch_ref_num = response && response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.merch_ref_num ? response.processingResponse.paymentDetails.merch_ref_num : '';
      let master_error = null;
      paymentAmount = parseInt(currentCartData.cartTotals.total_price);
      if (orderId && paymentAmount) {
        let currency_code = currentCartData.cartTotals.currency_code;
        let customer_first_name = currentCartData.billingAddress.first_name;
        let customer_last_name = currentCartData.billingAddress.last_name;
        let customer_email = currentCartData.billingAddress.email;
        let billing_street = currentCartData.billingAddress.address_1;
        let billing_street2 = currentCartData.billingAddress.address_2;
        let billing_city = currentCartData.billingAddress.city;
        let billing_zip = currentCartData.billingAddress.postcode;
        let billing_state = currentCartData.billingAddress.state;
        let billing_country = currentCartData.billingAddress.country;
        let billing_phone = currentCartData.billingAddress.phone;
        const checkout_options = {
          transactionSource: 'WooCommerceCheckout',
          amount: paymentAmount,
          transactionType: 'PAYMENT',
          currency: currency_code,
          merchantRefNum: merch_ref_num,
          environment: settings.test_mode ? 'TEST' : 'LIVE',
          displayPaymentMethods: ['paysafecash'],
          paymentMethodDetails: {
            paysafecash: {
              accountId: settings.account_id,
              consumerId: settings.consumer_id_encrypted
            }
          },
          threeDs: {
            merchantUrl: settings.checkout_url,
            deviceChannel: "BROWSER",
            messageCategory: "PAYMENT",
            authenticationPurpose: "PAYMENT_TRANSACTION",
            transactionIntent: "GOODS_OR_SERVICE_PURCHASE"
          },
          locale: settings.locale
        };
        if (settings.merchant_descriptor && settings.merchant_phone) {
          checkout_options.merchantDescriptor = {
            dynamicDescriptor: settings.merchant_descriptor,
            phone: settings.merchant_phone
          };
        }
        if (customerSingleUseToken) {
          checkout_options.singleUseCustomerToken = customerSingleUseToken;
        } else {
          checkout_options.customer = {
            firstName: customer_first_name,
            lastName: customer_last_name,
            email: customer_email
          };
          checkout_options.billingAddress = {
            nickName: "Home",
            zip: billing_zip,
            country: billing_country
          };
          if (billing_street) {
            checkout_options.billingAddress.street = billing_street;
          }
          if (billing_street2) {
            checkout_options.billingAddress.street2 = billing_street2;
          }
          if (billing_city) {
            checkout_options.billingAddress.city = billing_city;
          }
          if (billing_state) {
            checkout_options.billingAddress.state = billing_state;
          }
        }
        paysafe.checkout.setup(settings.authorization, checkout_options,
        // resultCallback
        function (instance, error, result) {
          if (result && result.paymentHandleToken) {
            // Successfully Tokenized transaction, use result.paymentHandleToken to process a payment
            // add AJAX code to send token to your merchant server

            const paymentData = {
              orderId: orderId,
              paymentMethod: result.paymentMethod,
              transactionType: result.transactionType,
              paymentHandleToken: result.paymentHandleToken,
              amount: result.amount,
              customerOperation: result.customerOperation,
              merchantRefNum: checkout_options.merchantRefNum
            };
            fetch(settings.register_url, {
              method: 'POST',
              credentials: 'same-origin',
              headers: new Headers({
                // 'Content-Type': 'application/x-www-form-urlencoded',
                'Content-Type': 'application/json'
              }),
              body: JSON.stringify(paymentData)
            }).then(result => {
              return result.json();
            }).then(json => {
              if (json.status === 'success') {
                instance.showSuccessScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Your goods are now purchased. Expect them to be delivered in next 5 business days.", "paysafe-checkout"));
              } else {
                instance.showFailureScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
              }
              next_page = json.redirect_url;
            }).catch(error => {
              // the BE call failed
              log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"), []);
              instance.showFailureScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"));
            });
          } else {
            let errorMessage = '';
            if (error) {
              if (error.code) {
                errorMessage += error.code + ' ';
              }
              if (error.message) {
                errorMessage += error.message + ' ';
              }
              if (error.detailedMessage) {
                errorMessage += error.detailedMessage;
              }
            }

            // Tokenization failed and Payment Handled moved to failed Status
            if (instance) {
              log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Payment failed. Popup was closed without a correct end message!", "paysafe-checkout") + ' ' + errorMessage, {
                'pace': 3
              });
              instance.showFailureScreen((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment was declined. Please, try again with the same or another payment method." + ' ' + errorMessage, "paysafe-checkout"));
            } else {
              master_error = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('The payment was declined. Please, try again with the same or another payment method.', "paysafe-checkout");
              if (error && error.code && error.detailedMessage) {
                master_error += "<br />" + ' ' + error.code + ' ' + error.detailedMessage;
              }
              log_paysafe_error(master_error, []);
              show_checkout_error(master_error);
            }
          }
        },
        // closeCallback
        function (stage, expired) {
          if (stage) {
            // Depeding upon the stage take different actions
            switch (stage) {
              case "PAYMENT_HANDLE_NOT_CREATED":
                // don't show any errors,
                // as the customer choose to close the popup window
                window.location.reload();
                break;
              case "PAYMENT_HANDLE_CREATED":
              case "PAYMENT_HANDLE_REDIRECT":
              case "PAYMENT_HANDLE_PAYABLE":
                // in all these cases the user closed the popup after the payment
                if (null !== next_page) {
                  window.location = next_page;
                  return;
                }

                // reload the cart page, something must have happened
                window.location.reload();
                break;
              default:
            }
          } else {
            // Add action in case Checkout is expired
            // the popup expired,
            // lets reload the page so that the customer
            // can try another payment option
            window.location.reload();
          }
        },
        // riskCallback
        function (instance, amount, paymentMethod) {
          if (amount === paymentAmount) {
            instance.accept();
          } else {
            log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Amount is not the value expected", "paysafe-checkout"), []);
            instance.decline((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Amount is not the value expected", "paysafe-checkout"));
          }
        });
      } else {
        log_paysafe_error((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout"), []);
        return {
          type: emitResponse.responseTypes.ERROR,
          message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("The payment process failed. Please close this popup and try again", "paysafe-checkout")
        };
      }
      return response;
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, '', [], onCheckoutSuccess]);
  let descriptionForDisplay = [(0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.description || ''))];
  if (settings.test_mode) {
    descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null));
    descriptionForDisplay.push((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      style: {
        textAlign: "justify",
        marginTop: "10px"
      }
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Paysafe Checkout is in TEST mode. Use the built-in simulator to test payments.', 'paysafe-checkout'))));
  }
  return descriptionForDisplay;
};
const PaysafePaysafeCashPayments = {
  name: paymentMethodId,
  paymentMethodId: paymentMethodId,
  label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Label, null),
  content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  canMakePayment: cartData => {
    currentCartData = cartData;
    return true;
  },
  ariaLabel: label,
  supports: {
    features: settings.supports
  }
};
if (!is_hosted_integration) {
  (0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__.registerPaymentMethod)(PaysafePaysafeCashPayments);
}
})();

/******/ })()
;
//# sourceMappingURL=blocks-paysafecash.js.map