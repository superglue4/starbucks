<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$SES_KEY = $this->input->post('KEY');
		$SES_USER = $this->session->userdata($SES_KEY);

		if (empty($SES_USER)) {
			return $this->load->view('view', array('status' => 308, 'url' => '/member/login', 'data' => '로그인 해주세요.'));
		}

		$ordnum = $this->input->get('ordnum');
		$buyer = $this->Buyer_model->select(array('now' => true, 'ordnum' => $ordnum));

		$conf_admin = $this->config->item('admin');
		$admin = in_array($SES_USER['name'], $conf_admin['member']);

		if (empty($buyer)) {
			if ($admin) {
				return $this->load->view('view', array('status' => 308, 'url' => '/admin', 'data' => '구매자가 없습니다. 관리자에게 문의하세요.'));
			}

			return $this->load->view('view', array('status' => 400, 'data' => '구매자가 생성되지 않았습니다. 관리자에게 문의하세요.'));
		}

		$menu = $this->Starbucks_model->select(array());
		$order = $this->Order_model->select(array('ordnum' => $buyer[0]['ordnum'], 'member_name' => $SES_USER['name']));

		$return = array(
			'user' => $SES_USER,
			'menu' => $menu,
			'buyer' => $buyer[0],
			'order' => isset($order[0]) ? $order[0] : array(),
			'timer' => date('m/d/Y H:i', strtotime($buyer[0]['end'])),
			'admin' => $admin
		);
		return $this->load->view('view', array('status' => 200, 'data' => $return));

	}

	/**
	 * 메뉴 가져오기
	 * @return object|string
	 */
	public function menu()
	{
		$code = $this->input->post('code');

		$menu = $this->Starbucks_model->select(array('product_cd' => $code));

		$info = array(
			"product_img" => $menu[0]['product_img'],
			"content" => $menu[0]['content']
		);

		return $this->load->view('json', array('status' => 200, 'data' => array('menu' => $info)));

	}

	/**
	 * 내 주문 보기
	 * @return object|string
	 */
	public function get()
	{
		$SES_KEY = $this->input->post('KEY');
		$SES_USER = $this->session->userdata($SES_KEY);
		$ordnum = $this->input->get_post('ordnum');

		if (empty($SES_USER)) {
			return $this->load->view('json', array('status' => 400, 'data' => '로그인 해주세요.'));
		}

		$order = $this->Order_model->select(array('member_name' => $SES_USER['name']));

		return $this->load->view('json', array('status' => 200, 'data' => array('order' => $order)));
	}


	/**
	 * 최종 주문서
	 * @return object|string
	 */
	public function prnt()
	{
		$SES_KEY = $this->input->post('KEY');
		$SES_USER = $this->session->userdata($SES_KEY);
		$ordnum = $this->input->get('ordnum');

		if (empty($SES_USER)) {
			return $this->load->view('json', array('status' => 400, 'data' => '로그인 해주세요.'));
		}

		$order = $this->Order_model->select(array('ordnum' => $ordnum));
		$arr = array();
		$total = 0;
		foreach ($order as $item) {
			$total += $item['product_cnt'];
			$cnt = $item['product_cnt'];
			if (!isset($arr[$item['product_nm']][$item['product_size']])) {
				$arr[$item['product_nm']] = array(
					'tall' => 0,
					'grande' => 0,
					'venti' => 0
				);
			}
			$arr[$item['product_nm']][$item['product_size']] = $arr[$item['product_nm']][$item['product_size']] + $cnt;
		}
		return $this->load->view('view', array('status' => 200, 'data' => array('order' => $arr, 'total' => $total, 'ordnum' => $ordnum)));
	}

	/**
	 * 회원별 주문서
	 * @return object|string
	 */
	public function mprnt()
	{
		$SES_KEY = $this->input->post('KEY');
		$SES_USER = $this->session->userdata($SES_KEY);
		$ordnum = $this->input->get('ordnum');

		if (empty($SES_USER)) {
			return $this->load->view('json', array('status' => 400, 'data' => '로그인 해주세요.'));
		}

		$order = $this->Order_model->select(array('ordnum' => $ordnum));
		$arr = array();
		$total = 0;
		foreach ($order as $item) {
			$total += $item['product_cnt'];
			$cnt = $item['product_cnt'];
			if (!isset($arr[$item['product_nm']][$item['product_size']])) {
				$arr[$item['product_nm']] = array(
					'tall' => array('cnt' => 0, 'comment' => array()),
					'grande' => array('cnt' => 0, 'comment' => array()),
					'venti' => array('cnt' => 0, 'comment' => array())
				);
			}
			$arr[$item['product_nm']][$item['product_size']]['cnt'] = $arr[$item['product_nm']][$item['product_size']]['cnt'] + $cnt;
			$arr[$item['product_nm']][$item['product_size']]['comment'][] = !empty($item['comment']) ? $item['name'] . ' : ' . $item['comment'] : $item['name'];
		}

		return $this->load->view('view', array('status' => 200, 'data' => array('order' => $arr, 'total' => $total, 'ordnum' => $ordnum)));
	}

	/**
	 * 주문하기
	 * @return object|string
	 */
	public function set()
	{
		$code = $this->input->post('menu_code');
		$size = $this->input->post('size');
		$cnt = $this->input->post('cnt');
		$comment = $this->input->post('comment');
		$ordnum = $this->input->post('ordnum');

		$SES_KEY = $this->input->post('KEY');
		$SES_USER = $this->session->userdata($SES_KEY);

		if (empty($SES_USER)) {
			return $this->load->view('json', array('status' => 400, 'data' => '로그인 해주세요.'));
		}
		if (empty($ordnum)) {
			return $this->load->view('json', array('status' => 400, 'data' => '주문번호가 없습니다.'));
		}
		if (empty($code)) {
			return $this->load->view('json', array('status' => 400, 'data' => '메뉴를 입력해주세요.'));
		}
		if (intval($cnt) > 5) {
			return $this->load->view('json', array('status' => 400, 'data' => '최대 5개까지 선택 가능합니다.'));
		}

		//다중 중복 주문 체크
		$dupl = $this->Order_model->check(array('ordnum' => $ordnum, 'member_name' => $SES_USER['name']));
		if ($dupl > 0) {
			return $this->load->view('json', array('status' => 400, 'data' => '중복하여 주문할 수 없습니다.'));
		}

		$menu = $this->Starbucks_model->select(array('product_cd' => $code));

		if (empty($menu)) {
			return $this->load->view('json', array('status' => 400, 'data' => '일치하는 메뉴가 없습니다.'));
		}

		$buyer = $this->Buyer_model->select(array('ordnum' => $ordnum, 'now' => true));

		if (empty($buyer)) {
			return $this->load->view('json', array('status' => 400, 'data' => '구매자가 생성되지 않았습니다.'));
		}

		$param = array(
			'ordnum' => $buyer[0]['ordnum'],
			'status' => '1',
			'member_name' => $SES_USER['name'],
			'product_cd' => $code,
			'product_size' => empty($size) ? 'tall' : $size,
			'product_cnt' => empty($cnt) ? 1 : intval($cnt),
			'comment' => $comment
		);

		$order = $this->Order_model->select(array('ordnum' => $buyer[0]['ordnum'], 'member_name' => $SES_USER['name']));

		if (!empty($order)) {
			$result = $this->Order_model->update($param);
		} else {
			$result = $this->Order_model->insert($param);
		}

		if (!$result) {
			return $this->load->view('json', array('status' => 400, 'data' => '주문 실패'));
		}

		return $this->load->view('json', array('status' => 200, 'data' => array('info' => $param, 'msg' => '주문 성공')));

	}


	/**
	 * 주문 시작
	 * @return object|string
	 */
	public function start()
	{
		$name = $this->input->post('name');
		$time = $this->input->post('time');
		$comment = $this->input->post('comment');
		$option = $this->input->post('option');

		$SES_KEY = $this->input->post('KEY');
		$SES_USER = $this->session->userdata($SES_KEY);

		$admin = $this->config->item('admin');

		if (!(in_array($SES_USER['name'], $admin['member']))) {
			return $this->load->view('json', array('status' => 400, 'data' => '주문자를 생성할 권한이 없습니다.'));
		}

		if (empty($name)) {
			return $this->load->view('json', array('status' => 400, 'data' => '이름을 입력해주세요.'));
		}
		if (empty($time)) {
			return $this->load->view('json', array('status' => 400, 'data' => '시간을 입력해주세요.'));
		}
		if (empty($comment)) {
			return $this->load->view('json', array('status' => 400, 'data' => '코멘트를 입력해주세요.'));
		}

//		$buyer = $this->Buyer_model->select(array('now' => true));
//		if (count($buyer) > 0) {
//			return $this->load->view('json', array('status' => 400, 'data' => '생성된 주문이 존재합니다. 아직 주문이 완료되지 않았습니다.'));
//		}

		$file_name = '/tmp/drink.log';
		$period = date("Ymd" , strtotime('now'));

		if (!is_file($file_name)) {
			return $this->load->view('view', array('status' => 400, 'data' => '음료 데이터를 생성하십시오.'));
		}
		$drink = '';
		if (date("Ymd" ,filemtime($file_name)) < $period) {
			$this->Starbucks_model->fetch();
			$drink = 'drink updated';
		}

		$this->Buyer_model->insert(array(
			'ordnum' => uniqid(),
			'member_name' => $name,
			'start' => date('Y-m-d H:i:s'),
			'end' => date('Y-m-d H:i:s', strtotime($time . ' hours')),
			'comment' => $comment,
			'option' => $option // 0 : 옵션 안 받기, 1 : 옵션 받기
		));

		return $this->load->view('json', array('status' => 200, 'data' => '주문이 생성 되었습니다. ' . $drink . ' 메뉴 업데이트일: ' . $period));
	}

	/**
	 * 주문 삭제
	 * @return object|string
	 */
	public function delete()
	{
		$ordnum = $this->input->post('ordnum');

		if ($this->Buyer_model->delete(array('ordnum' => $ordnum))) {
			return $this->load->view('json', array('status' => 200, 'data' => '삭제 되었습니다.'));
		}
		return $this->load->view('json', array('status' => 400, 'data' => '삭제 실패'));
	}
}
