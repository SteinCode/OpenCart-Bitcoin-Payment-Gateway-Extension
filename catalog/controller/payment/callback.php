<?php

namespace Opencart\Catalog\Controller\Extension\Spectrocoin\Payment;

require_once DIR_EXTENSION . 'spectrocoin/system/library/spectrocoin/Http/OrderCallback.php';
require_once DIR_EXTENSION . 'spectrocoin/system/library/spectrocoin/Http/OldOrderCallback.php';
require_once DIR_EXTENSION . 'spectrocoin/system/library/spectrocoin/Enum/OrderStatus.php';

use Opencart\Catalog\Controller\Extension\Spectrocoin\Payment\Http\OrderCallback;
use Opencart\Catalog\Controller\Extension\Spectrocoin\Payment\Http\OldOrderCallback;
use Opencart\Catalog\Controller\Extension\Spectrocoin\Payment\Enum\OrderStatus;

use Exception;
use InvalidArgumentException;

use GuzzleHttp\Exception\RequestException;

use Opencart\System\Engine\Controller;

class Callback extends Controller
{
    public function index()
    {
        try {
            $this->load->model('checkout/order');
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                $this->log->write('SpectroCoin Error: Invalid request method, POST is required');
                exit;
            }

            $order_callback = $this->initCallbackFromPost();
            if (!$order_callback) {
                $this->log->write('SpectroCoin Error: Invalid callback data');
                exit;
            }
            $order_id = explode("-", ($order_callback->getOrderId()))[0];
            $order = $this->model_checkout_order->getOrder($order_id);
            $status = $order_callback->getStatus();
            if ($order) {
                switch ($status) {
                    case OrderStatus::New->value:
                        break;
                    case OrderStatus::Pending->value:
                        $this->model_checkout_order->addHistory($order_id, 2);
                        break;
                    case OrderStatus::Expired->value:
                        $this->model_checkout_order->addHistory($order_id, 14);
                        break;
                    case OrderStatus::Failed->value:
                        $this->model_checkout_order->addHistory($order_id, 7);
                        break;
                    case OrderStatus::Paid->value:
                        $this->model_checkout_order->addHistory($order_id, 15);
                        break;
                    default:
                        $this->log->write('SpectroCoin Callback: Unknown order status - ' . $status);
                        echo 'Unknown order status: ' . $status;
                        exit;
                }
                http_response_code(200);
                echo '*ok*';
                exit;
            } else {
                $this->log->write('SpectroCoin Error: Order not found - Order ID: ' . $order_id);
                http_response_code(404); // Not Found
                exit;
            }
        } catch (RequestException $e) {
            $this->log->write("Callback API error: {$e->getMessage()}");
            http_response_code(500); // Internal Server Error
            echo esc_html__('Callback API error', 'spectrocoin-accepting-bitcoin');
            exit;
        } catch (InvalidArgumentException $e) {
            $this->log->write("Error processing callback: {$e->getMessage()}");
            http_response_code(400); // Bad Request
            echo esc_html__('Error processing callback', 'spectrocoin-accepting-bitcoin');
            exit;
        } catch (Exception $e) {
            $this->log->write('error', "Error processing callback: {$e->getMessage()}");
            http_response_code(500); // Internal Server Error
            echo esc_html__('Error processing callback', 'spectrocoin-accepting-bitcoin');
            exit;
        }
    }

    /**
     * Initializes the callback data from POST (form-encoded) request.
     * 
     * Callback format processed by this method is URL-encoded form data.
     * Example: merchantId=1387551&apiId=105548&userId=…&sign=…
     * Content-Type: application/x-www-form-urlencoded
     * These callbacks are being sent by old merchant projects.
     *
     * Extracts the expected fields from `$_POST`, validates the signature,
     * and returns an `OldOrderCallback` instance wrapping that data.
     *
     * @deprecated since v2.1.0
     *
     * @return OldOrderCallback|null  An `OldOrderCallback` if the POST body
     *                                contained valid data; `null` otherwise.
     */
    private function initCallbackFromPost(): ?OldOrderCallback
    {
        $expected_keys = ['userId', 'merchantApiId', 'merchantId', 'apiId', 'orderId', 'payCurrency', 'payAmount', 'receiveCurrency', 'receiveAmount', 'receivedAmount', 'description', 'orderRequestId', 'status', 'sign'];

        $callback_data = [];
        foreach ($expected_keys as $key) {
            if (isset($_POST[$key])) {
                $callback_data[$key] = $_POST[$key];
            }
        }

        if (empty($callback_data)) {
            $this->log->write("No data received in callback");
            return null;
        }
        return new OldOrderCallback($callback_data);
    }


    /**
     * Initializes the callback data from JSON request body.
     *
     * Reads the raw HTTP request body, decodes it as JSON, and returns
     * an OrderCallback instance if the payload is valid.
     *
     * @return OrderCallback|null  An OrderCallback if the JSON payload
     *                             contained valid data; null if the body
     *                             was empty.
     *
     * @throws \JsonException           If the request body is not valid JSON.
     * @throws \InvalidArgumentException If required fields are missing
     *                                   or validation fails in OrderCallback.
     *
     */
    private function initCallbackFromJson(): ?OrderCallback
    {
        $body = (string) \file_get_contents('php://input');
        if ($body === '') {
            $this->log->write('Empty JSON callback payload');
            return null;
        }

        $data = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!\is_array($data)) {
            $this->log->write('JSON callback payload is not an object');
            return null;
        }

        return new OrderCallback(
            $data['id'] ?? null,
            $data['merchantApiId'] ?? null
        );
    }
}
