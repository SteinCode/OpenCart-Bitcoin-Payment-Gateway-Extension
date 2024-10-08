<?php

declare(strict_types=1);

namespace Opencart\Admin\Controller\Extension\Spectrocoin\Payment;

use Opencart\System\Engine\Controller;

class Spectrocoin extends Controller
{
    private array $error = [];

    private array $langs = [
        'heading_title', 'text_edit', 'text_enabled', 'text_disabled', 'text_all_zones', 'text_none',
        'text_yes', 'text_no', 'text_off', 'entry_project', 'entry_client_id', 'entry_client_secret', 'entry_sign', 'entry_lang', 'help_lang', 'entry_test',
        'entry_order_status', 'entry_geo_zone', 'entry_receive_currency', 'entry_status', 'entry_default_payments', 'entry_display_payments',
        'entry_sort_order', 'button_save', 'button_cancel', 'tab_general', 'text_default_title', 'entry_title',
        'text_spectrocoin', 'info_heading', 'info_desc', 'info_step_1', 'info_step_2', 'info_step_3',
        'info_step_4', 'info_step_5', 'info_step_6', 'info_step_7', 'info_step_8', 'info_step_9', 'info_step_10', 'info_note', 'status_checkbox_label'
    ];

    /**
     * Index method for handling the Spectrocoin payment module settings.
     *
     * @return void
     */
    public function index(): void
    {
        $this->load->model('localisation/order_status');
        $this->load->language('extension/spectrocoin/payment/spectrocoin');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_spectrocoin', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/spectrocoin/payment/spectrocoin', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = [];

        foreach ($this->langs as $lang) {
            $data[$lang] = $this->language->get($lang);
        }

        if (!empty($this->error)) {
            foreach ($this->error as $key => $error) {
                $data['error_' . $key] = $error;
            }
        }

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_payment'),
                'href' => $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/spectrocoin/payment/spectrocoin', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        $data['action'] = $this->url->link('extension/spectrocoin/payment/spectrocoin', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = HTTP_SERVER . 'index.php?route=extension/payment&user_token=' . $this->session->data['user_token'];

        $keys = [
            'payment_spectrocoin_title',
            'payment_spectrocoin_project',
            'payment_spectrocoin_client_id',
            'payment_spectrocoin_client_secret',
            'payment_spectrocoin_status',
            'payment_spectrocoin_sort_order'
        ];

        foreach ($keys as $key) {
            $data[$key] = $this->request->post[$key] ?? $this->config->get($key);
        }

        $data['callback'] = HTTP_CATALOG . 'index.php?route=payment/spectrocoin/callback';
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['spectrocoin_css'] = '<link href="' . HTTP_CATALOG . 'extension/spectrocoin/admin/view/stylesheet/spectrocoin.css" rel="stylesheet" type="text/css" />';
        $data['spectrocoin_logo'] = HTTP_CATALOG . 'extension/spectrocoin/admin/view/image/payment/spectrocoin-logo.svg';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/spectrocoin/payment/spectrocoin', $data));
    }

    /**
     * Validates the form submission.
     *
     * @return bool True if the form is valid, false otherwise.
     */
    private function validate(): bool
    {
        if (!$this->user->hasPermission('modify', 'extension/spectrocoin/payment/spectrocoin')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return empty($this->error);
    }
}
