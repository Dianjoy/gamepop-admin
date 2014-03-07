<?php

/**
 * @overview 封装一下mustache模版
 * @author Meathill (lujia.zhai@dianjoy.com)
 * @since 2.0
 */
require(dirname(__FILE__) . '/mustache.php');

class Template {
  
  /**
   * 模版内容
   * @var String 
   */
  private $template = '';
  /**
   * Mustache模版引擎实例
   * @var Mustache
   */
  private $mustache;
  /**
   * 用来存储一些附加内容
   * @var Array
   */
  private $extra = array();

  /**
   * 封装mustache，这样可以少复制一些代码
   * @param String $template 模版路径
   */
  public function __construct($template = '') {
    $this->template = file_get_contents($template);
    $this->mustache = new Mustache_Engine;
  }
  
  /**
   * 将数据通过模版渲染出来
   * @param Array $data
   */
  public function render(Array $data = array()) {
    $data = array_merge($data, $this->extra);
    return $this->mustache->render($this->template, $data);
  }
  
  /**
   * 将模板中符合要求的部分提取出来，作为内容保存起来
   * @param String $key
   * @param String $pattern
   */
  public function extract($key, $pattern) {
    $matches = null;
    preg_match($pattern, $this->template, $matches);
    $this->extra[$key] = $matches[0];
  }
}
?>
