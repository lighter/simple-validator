<?php

class validator_error_message
{
    /**
     * 錯誤訊息
     *
     * @var array
     */
    public $message = [];

    /**
     * 錯誤代碼
     *
     * @var string
     */
    public $code = '';

    /**
     * 原始資料內容
     *
     * @var array
     */
    public $original_data = [];

    /**
     * 錯誤的資料
     *
     * @var array
     */
    public $error_data = [];

    /**
     * 記錄細節其他錯誤資訊
     *
     * @var array
     */
    public $detail_message = [];

    public function __construct($message = [], $code = '', $original_data = [], $error_data = [], $detail_message = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->original_data = $original_data;
        $this->error_data = $error_data;
        $this->detail_message = $detail_message;
    }
}

class validation
{
   const PRIVATE_VARIABLE_PREFIX = '_v_';

    /**
     * 中間檢查過程記錄錯誤訊息
     *
     * @var array
     */
    private $_error_log_message = [];

    /**
     * 驗證結果
     *
     * @var boolean
     */
    private $_v_validation_status = TRUE;


    /**
     * 驗證規則
     *
     * @var array
     */
    private $_v_rules = [];

    /**
     * 驗證資料
     *
     * @var array
     */
    private $_v_datas = [];

    /**
     * 驗證錯誤訊息
     *
     * @var array
     */
    private $_v_error_message = [];

    /**
     * 客製化訊息
     *
     * @var array
     */
    private $_v_custom_detail_message = [];

    public function __construct($rules = [], $datas = [], $message = [])
    {
        $this->_v_rules = $rules;
        $this->_v_datas = $datas;
        $this->_v_custom_detail_message = $message;
    }

    /**
     * setter
     *
     * @param string $property 變數名稱
     * @param mixed  $data     值
     *
     * @return
     */
    public function __set($property = '', $data = '')
    {
        $name = self::PRIVATE_VARIABLE_PREFIX . $property;

        if (isset($this->$name))
        {
            $this->$name = $data;
        }

        return FALSE;
    }

    /**
     * getter
     *
     * @param string $property 變數名稱
     *
     * @return void
     */
    public function __get($property = '')
    {
        $name = self::PRIVATE_VARIABLE_PREFIX . $property;

        if (isset($this->$name))
        {
            return $this->$name;
        }

        return FALSE;
    }

    /**
     * 欄位值搜尋
     *
     * @param array  $data       搜尋資料
     * @param string $target_key 搜尋 key
     *
     * @return array
     */
    function search_array_by_key($data = [], $target_key = '')
    {
        $results = [];

        if (is_array($data) && ! empty($target_key))
        {
            # key 去空白
            $target_key = trim($target_key);

            # EX desc.content 指定陣列階層的資料
            if (strpos($target_key, '.') !== FALSE)
            {
                # 階層的搜尋 key
                $results = $this->search_array_by_level_key($data, $target_key);
            }
            else
            {
                if (isset($data[$target_key]))
                {
                    $results[] = $data[$target_key];
                }
                else
                {
                    foreach ($data as $sub_data)
                    {
                        $results = array_merge($results, $this->search_array_by_key($sub_data, $target_key));
                    }
                }
            }
        }

        return $results;
    }

    /**
     * 指定階層的搜尋 array
     *
     * @param array  $data      搜尋資料
     * @param string $level_key 搜尋 key
     *
     * @return array
     */
    public function search_array_by_level_key($data = [], $level_key = '')
    {
        $results = [];

        if (is_array($data) && ! empty($level_key))
        {
            # key 去空白
            $level_key = trim($level_key);

            $level_keys = explode('.', $level_key);

            if ( ! empty($level_keys))
            {
                $lenght = count($level_keys);
                $temp_array = [];

                foreach ($level_keys as $index => $t_key)
                {
                    if ($index === 0 && isset($data[$t_key]))
                    {
                        $temp_array = $data[$t_key];
                    }
                    else
                    {
                        if (isset($temp_array[$t_key]))
                        {
                            $temp_array = $temp_array[$t_key];
                        }
                    }

                    if ( ! empty($temp_array)
                        && ($index + 1) === $lenght
                        && is_array($temp_array))
                    {
                        foreach ($temp_array as $temp_data)
                        {
                            if (isset($temp_data[$t_key]))
                            {
                                $results[] = $temp_data[$t_key];
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * 必填欄位
     *
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    public function required($key = '', $message = 'is required', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if (empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    $this->_v_alidation_status = FALSE;
                    $this->_error_log_message[] = $message;
                    $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                    break;
                }
            }
        }
    }

    /**
     * 最大長度
     *
     * @param string $key        欄位名稱
     * @param string $max_length 最大長度
     * @param string $message    指定訊息
     * @param string $code       指定code
     *
     * @return void
     */
    public function max_length($key = '', $max_length = '', $message = 'is too long', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if (strlen($data) > $max_length)
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 最小長度
     *
     * @param string $key        欄位名稱
     * @param string $min_length 最小長度
     * @param string $message    指定訊息
     * @param string $code       指定code
     *
     * @return void
     */
    public function min_length($key = '', $min_length = '', $message = 'is too short', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if (strlen($data) < $min_length)
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 檢查字串長度是否在範圍內
     *
     * @param string  $key        欄位名稱
     * @param integer $min_length 最小長度
     * @param integer $max_length 最大長度
     * @param string  $message    指定訊息
     * @param string  $code       指定code
     *
     * @return void
     */
    public function between_length($key = '', $min_length = '', $max_length = '', $message = 'length exceeds limits', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                var_dump($key_datas);

                foreach ($key_datas as $data)
                {
                    if (strlen($data) < $min_length || strlen($data) > $max_length)
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 檢查是否為整數
     *
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    public function integer($key = '', $message = 'is not integer', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if ( ! is_int($data))
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 檢查是否為數字
     *
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    public function numeric($key = '', $message = 'is not numeric', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if ( ! is_numeric($data))
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 檢查是否為浮點數
     *
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    public function float($key = '', $message = 'is not float', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if ( ! is_float($data))
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 正規式驗證
     *
     * @param string $key          欄位名稱
     * @param string $regx_pattern 正規式
     * @param string $message      指定訊息
     * @param string $code         指定code
     *
     * @return void
     */
    public function regx($key = '', $regx_pattern = '', $message = '', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if ( ! preg_match($regx_pattern, $data))
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 簡單驗證資料格式是否為 "時間"" 格式
     *
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    public function date($key = '', $message = 'is not date', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if (strtotime($data) === FALSE)
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 驗證時間格式
     *
     * @param string $key                  欄位名稱
     * @param string $date_formate_pattern 時間格式
     * @param string $message              指定訊息
     * @param string $code                 指定code
     *
     * @return void
     */
    public function date_formate($key = '', $date_formate_pattern = '', $message = 'date_formate error', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    $d = DateTime::createFromFormat($date_formate_pattern, $data);

                    if ( ! ($d && $d->format($date_formate_pattern) == $data))
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 檢查 json formate
     *
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    public function json($key = '', $message = '', $code = '')
    {
        if ( ! empty($key))
        {
            # 找出該key的值
            $key_datas = $this->search_array_by_key($this->_v_datas, $key);

            if ( ! empty($key_datas))
            {
                foreach ($key_datas as $data)
                {
                    if (is_string($data))
                    {
                        json_decode($data);

                        if (json_last_error() !== JSON_ERROR_NONE)
                        {
                            $this->_v_alidation_status = FALSE;
                            $this->_error_log_message[] = $message;
                            $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data, json_last_error())));

                            break;
                        }
                    }
                    else
                    {
                        $this->_v_alidation_status = FALSE;
                        $this->_error_log_message[] = $message;
                        $this->_v_error_message[$key] = get_object_vars(new validator_error_message($this->_error_log_message, $code, $key_datas, $data));

                        break;
                    }
                }
            }
        }
    }

    /**
     * 執行資料驗證
     *
     * @return boolean
     */
    public function run()
    {
        try
        {
            if (empty($this->_v_rules))
                throw new Exception('', 201);

            if (empty($this->_v_datas))
                throw new Exception('', 202);

            foreach ($this->_v_rules as $column_key => $rule_setting)
            {
                # 確認有設定規則
                if ( ! empty($rule_setting['rules']))
                {
                    # 清空陣列
                    $this->_error_log_message = [];

                    # 規則分割
                    $rules = explode('|', $rule_setting['rules']);

                    foreach ($rules as $rule)
                    {
                        $message_key = "{$column_key}.{$rule}";
                        $message = isset($this->_v_custom_detail_message[$message_key]) ? $this->_v_custom_detail_message[$message_key] : $rule_setting['message'];

                        # 執行驗證
                        $this->_validation($rule, $column_key, $message, $rule_setting['code']);
                    }
                }
            }
        }
        catch (Exception $e)
        {
            $this->_v_alidation_status = FALSE;

            if ($e->getCode() == 201)
            {
                $message = 'You must set the rules';
            }

            if ($e->getCode() == 202)
            {
                $message = 'You must set datas';
            }

            $this->_v_error_message = [
                'message' => $message,
                'code' => $e->getCode(),
            ];
        }

        return $this->_v_alidation_status;
    }

    /**
     * 資料驗證
     *
     * @param string $rule    規則
     * @param string $key     欄位名稱
     * @param string $message 指定訊息
     * @param string $code    指定code
     *
     * @return void
     */
    private function _validation($rule = '', $key = '', $message = '', $code = '')
    {
        if ( ! empty($rule))
        {
            # required
            if (preg_match('/required/', $rule))
            {
                if (method_exists($this, 'integer'))
                {
                    $this->required($key, $message, $code);
                }
            }

            # max_length[5], min_length[3]
            if (preg_match('/(.*)?\[([0-9]*)\]/', $rule, $match))
            {
                $method = $match[1];
                $param = $match[2];

                if (method_exists($this, $method))
                {
                    $this->$method($key, $param, $message, $code);
                }
            }

            # between_length[2,3]
            if (preg_match('/(.*)?\[([0-9]*)\,([0-9]*)\]/', $rule, $match))
            {
                $method = $match[1];
                $param_1 = $match[2];
                $param_2 = $match[3];

                if (method_exists($this, $method))
                {
                    $this->$method($key, $param_1, $param_2, $message, $code);
                }
            }

            # regular expression
            if (preg_match('/(regx)\[(.*)\]/', $rule, $match))
            {
                $method = $match[1];
                $regx_pattern = $match[2];

                if (method_exists($this, $method))
                {
                    $this->$method($key, $regx_pattern, $message, $code);
                }
            }

            # date formate
            if (preg_match('/(date_formate)\[(.*)\]/', $rule, $match))
            {
                $method = $match[1];
                $date_formate_pattern = $match[2];

                if (method_exists($this, $method))
                {
                    $this->$method($key, $date_formate_pattern, $message, $code);
                }
            }

            # interger, numeric, float, date, json
            if (preg_match('/(.*)?/', $rule, $match))
            {
                $method = $match[1];

                if (method_exists($this, $method))
                {
                    $this->$method($key, $message, $code);
                }
            }
        }
    }
}


class test
{
    public $validator = null;

    public function __construct()
    {
        $this->validator = new Validation();
    }

    public function test_validate()
    {
        $data = [
            'cc_seq' => 'test ',
            'itno' => '2015BB12345678',
            'color' => 'red',
            'size' => 'x',
            'desc' => [
                [
                    'content' => '12345678',
                    'is_post' => 123456
                ],
                [
                    'content' => '5678',
                    'is_post' => 9999
                ]
            ],
            'detail' => [
                [
                    'content' => 'abcdefgh',
                    'is_post' => 123456
                ],
                [
                    'content' => 'qwerasdfzdx',
                    'is_post' => 9999
                ]
            ],
            'ins_dt' => '2015/12/23 14:20',
            'json_data' => (['test' => '123']),
        ];

        $rules = [
            'cc_seq'         => ['rules' => 'required', 'message' => 'test', 'code' => '201'],
            'color'          => ['rules' => 'required|max_length[4]', 'message' => 'color error', 'code' => '202'],
            'itno'           => ['rules' => 'regx[/[0-9]{4}AH[0-9]{8}/]', 'message' => 'iton error', 'code' => '203'],
            'desc.content'   => ['rules' => 'max_length[5]', 'message' => 'content error', 'code' => '204'],
            'detail.content' => ['rules' => 'max_length[5]', 'message' => 'content error', 'code' => '205'],
            'is_post'        => ['rules' => 'between_length[4,5]', 'message' => 'is_post error', 'code' => '206'],
            'ins_dt'         => ['rules' => 'max_length[5]|date_formate[Y-m-d H:i]', 'message' => 'ins_dt error', 'code' => '207'],
            'json_data'      => ['rules' => 'json', 'message' => 'json_data error', 'code' => '208'],
        ];

        // $this->validator->rules = [
        //     'cc_seq'         => ['rules' => 'required', 'message' => 'test', 'code' => '201'],
        //     'color'          => ['rules' => 'required|max_length[4]', 'message' => 'color error', 'code' => '202'],
        //     'itno'           => ['rules' => 'regx[/[0-9]{4}AH[0-9]{8}/]', 'message' => 'iton error', 'code' => '203'],
        //     'desc.content'   => ['rules' => 'max_length[5]', 'message' => 'content error', 'code' => '204'],
        //     'detail.content' => ['rules' => 'max_length[5]', 'message' => 'content error', 'code' => '205'],
        //     'is_post'        => ['rules' => 'between_length[4,5]', 'message' => 'is_post error', 'code' => '206'],
        //     'ins_dt'         => ['rules' => 'max_length[5]|date_formate[Y-m-d H:i]', 'message' => 'ins_dt error', 'code' => '207'],
        // ];

        # 針對規則客製化訊息

        $custom_detail_message = [
            'cc_seq.required'            => 'cc_seq 為必填欄位',
            'color.required'             => 'color 為必填欄位',
            'color.max_length[4]'        => 'color 最多為 4 位',
            'desc.content.max_length[5]' => 'desc content 最多為 5 位',
            'ins_dt.date_formate[Y-m-d H:i]' => '新增時間格式錯誤',
            'ins_dt.max_length[5]' => '新增時間長度最多為5',
            'json_data.json' => '必須為json字串',
        ];

        // $this->validator->custom_detail_message = [
        //     'cp_seq.required'            => 'cp_seq 為必填欄位',
        //     'color.required'             => 'color 為必填欄位',
        //     'color.max_length[4]'        => 'color 最多為 4 位',
        //     'desc.content.max_length[5]' => 'desc content 最多為 5 位',
        //     'ins_dt.date_formate[Y-m-d H:i]' => '新增時間格式錯誤',
        //     'ins_dt.max_length[5]' => '新增時間長度最多為5'
        // ];

        // $this->validator->datas = $data;

        $this->validator = new Validation($rules, $data, $custom_detail_message);

        echo "run result: ";
        var_dump($this->validator->run());

        echo '<hr>';

        echo "error_message: ";
        echo '<pre>';
        var_dump($this->validator->error_message);
        echo '</pre>';
    }
}

$test = new test();

$test->test_validate();
