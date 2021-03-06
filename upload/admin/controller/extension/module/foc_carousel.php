<?php

class ControllerExtensionModuleFocCarousel extends Controller {

  public function install () {

  }

  public function uninstall () {

  }


  private function isOpencart3 () {
    $version_chars = explode('.', VERSION);
    return (int)$version_chars[0] === 3;
  }

  private function getTokenParam () {
    if (isset($this->session->data['user_token'])) {
      return 'user_token=' . $this->session->data['user_token'];
    }
    else {
      return 'token=' . $this->session->data['token'];
    }
  }

  private function createUrl ($url) {
    return $this->url->link($url, $this->getTokenParam(), 'SSL');
  }

  public function index () {
    $this->load->language('extension/module/foc_carousel');
    $this->load->model('localisation/language');

    if ($this->isOpencart3()) {
      $this->load->model('setting/module');
      $moduleModel = $this->model_setting_module;
    }
    else {
      $this->load->model('extension/module');
      $moduleModel = $this->model_extension_module;
    }

    $this->load->model('tool/image');

    $this->document->addStyle('view/javascript/jquery/jquery-ui/jquery-ui.min.css');
    $this->document->addScript('view/javascript/jquery/jquery-ui/jquery-ui.min.js');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $carousel = array();
      $post = $this->request->post;

      foreach ($post['foc_carousel'] as $language_id => $slides) {
        foreach ($slides as $slide) {
          $carousel[$language_id][$slide['weight']] = $slide;
        }
      }

      $post['foc_carousel'] = $carousel;

			if (!isset($this->request->get['module_id'])) {
				$moduleModel->addModule('foc_carousel', $post);
			} else {
				$moduleModel->editModule($this->request->get['module_id'], $post);
			}

			$this->session->data['success'] = $this->language->get('text_success');

      if ($this->isOpencart3()) {
        $this->response->redirect($this->url->link('marketplace/extension', $this->getTokenParam() . '&type=module', true));
      }
      else {
        $this->response->redirect($this->url->link('extension/extension', $this->getTokenParam() . '&type=module', true));
      }
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/foc_carousel', $this->getTokenParam(), true);
		} else {
			$data['action'] = $this->url->link('extension/module/foc_carousel', $this->getTokenParam() . '&module_id=' . $this->request->get['module_id'], true);
    }

    if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $moduleModel->getModule($this->request->get['module_id']);
    }

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
    }

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
    }

		if (isset($this->request->post['slides_count'])) {
			$data['slides_count'] = $this->request->post['slides_count'];
		} elseif (!empty($module_info)) {
			$data['slides_count'] = $module_info['slides_count'];
		} else {
			$data['slides_count'] = 4;
    }

		if (isset($this->request->post['template_postfix'])) {
			$data['template_postfix'] = $this->request->post['template_postfix'];
		} elseif (!empty($module_info)) {
			$data['template_postfix'] = $module_info['template_postfix'];
		} else {
			$data['template_postfix'] = '';
    }

		$data['languages'] = $this->model_localisation_language->getLanguages();
    $data['language_id'] = $this->config->get('config_language_id');

    $data['slides'] = array();
    $data['counter'] = array();
    $data['settings'] = array();

    foreach ($data['languages'] as $language) {
      $data['counter'][$language['language_id']] = 0;
      if (isset($module_info['foc_carousel_settings'][$language['language_id']])) {
        $data['settings'][$language['language_id']] = $module_info['foc_carousel_settings'][$language['language_id']];
      }
      else {
        $data['settings'][$language['language_id']] = array(
          'crop_horizontal' => '',
          'crop_vertical' => ''
        );
      }
    }

    if (isset($module_info['foc_carousel'])) {
      $data['slides'] = $module_info['foc_carousel'];

      foreach ($data['slides'] as $language_id => $slides) {
        ksort($data['slides'][$language_id]);

        $data['counter'][$language_id] = max(array_column($slides, 'weight'));

        foreach ($slides as $i => $slide) {
          if (isset($slide['type']) && $slide['type'] == 'image') {
            if (is_file(DIR_IMAGE . $slide['content'])) {
              $image = $slide['content'];
              $thumb = $slide['content'];
            } else {
              $image = '';
              $thumb = 'no_image.png';
            }

            $data['slides'][$language_id][$i]['thumb'] = $this->model_tool_image->resize($thumb, 100, 100);
          }
        }
      }
    }

		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
    $data['column_left'] = $this->load->controller('common/column_left');

    $data['button_add_slide'] = $this->language->get('button_add_slide');
    $data['button_remove_slide'] = $this->language->get('button_remove_slide');
		$data['button_cancel'] = $this->language->get('cancel');
		$data['button_save'] = $this->language->get('save');

    $data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
    $data['entry_name'] = $this->language->get('entry_name');
    $data['entry_status'] = $this->language->get('entry_status');
    $data['entry_slides_count'] = $this->language->get('entry_slides_count');
    $data['entry_template_postfix'] = $this->language->get('entry_template_postfix');
    $data['entry_carousel_image_link'] = $this->language->get('entry_carousel_image_link');
    $data['entry_carousel_youtube_width'] = $this->language->get('entry_carousel_youtube_width');
    $data['entry_carousel_youtube_height'] = $this->language->get('entry_carousel_youtube_height');

    $data['entry_crop_horizontal'] = $this->language->get('entry_crop_horizontal');
    $data['entry_crop_vertical'] = $this->language->get('entry_crop_vertical');

    $data['labels'] = array();

		$data['labels']['foc_add2cart_box_info_tab_name'] = $this->language->get('foc_add2cart_box_info_tab_name');
		$data['labels']['foc_add2cart_box_info_tab_title'] = $this->language->get('foc_add2cart_box_info_tab_title');
		$data['labels']['foc_add2cart_box_info_tab_content'] = $this->language->get('foc_add2cart_box_info_tab_content');

    $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		return $this->response->setOutput($this->load->view('extension/module/foc_carousel', $data));
  }

  private function validate () {
    return true;
  }

  private function breadcrumbs () {
    $breadcrumbs = array();

    $breadcrumbs[] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->createUrl('common/home'),
			'separator' => false
    );
    $breadcrumbs[] = array(
      'text'      => $this->language->get('text_extension'),
      'href'      => $this->isOpencart3() ? $this->createUrl('marketplace/extension') : $this->createUrl('extension/extension'),
      'separator' => ' :: '
    );
		$breadcrumbs[] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->createUrl('extension/module/foc_add2cart_box'),
			'separator' => ' :: '
    );

    return $breadcrumbs;
  }
}