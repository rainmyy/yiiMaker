<?php
/**
* @Title 控制器创建文档
* @author Chenzhenghao
* @date 2015-12-23
*/
namespace console\controllers;
use Yii;
use common\library\RenderMessage;
class MkdirController extends \yii\console\Controller{
	private $_appName             = '';
	private $_tableName           = '';
	private $_theName             = '';
	private $_restName            = '';
	private $_funcName            = '';
	private $_apiConName          = '';
	private $_modelName           = '';
	private $_modelInterfaceName  = '';
	private $_rootDirect          = '';
	private $_appDirect           = '';
	private $_apiDirect           = '';

	/**************调用入口****************/
	
	/***
	* @Title         自动生成文件入口
	* @note          文档默认生成的部分有：api-controller, api-model,model文件夹，model,model-interface, 如果app不存在，自动生成app
	* @note           默认的传参顺序为: app,table_name,func_name,func参数
	* @note           系统默认自动生成的 functrion 为showList,add,show,update,del
	*/
	public  function actionRun($args = '') {
		$appName = $tableName = $funcName = '';	
		
		if($args == '') {
		
			$this->_getMessage(10001, '参数不能为空');
			return;
		}
		
		$args = explode(',', $args);
		$count = count($args);
		if($count == 2) {
			
			$appName = $args[0];
			$tableName = $args[1];
		} elseif($count ==  3) {
			
			$appName = $args[0];
			$tableName = $args[1];
			$funcName = $args[2];
			
			$this->_tableName = $tableName;
			$this->_funcName = $funcName;
		} else {
			$appName = $args[0];
		}
		
		if($appName == '' || $tableName == '') {
			
			$this->_getMessage(10001, '参数错误');
		}
		
		if($this->MakeName($tableName) == FALSE) {
			
			$this->_getMessage(10002, '参数生成失败');
		}
	
		$this->getName($this->_tableName);
		$this->getApp($appName);
		$this->getDirect();
		
		$this->makeApp();
		$this->makeDocument();
		//print_r($this->makeModelInterface());
		print_r($this->makeModel());
		$this->makeApi();
	}
	
	/****************基础方法*****************/
	
	private function iif($a = '', $b = '', $e = '') {
	 
		if ($a) {
			return $b;
		} else {
			return $e;
		}
	}
	
	private function eiif($a = '', $b = '', $c = '', $d = '', $e = '') {

		if ($a) {
			return $b;
		} elseif ($c) {
			return $d;
		} else {
			return $e;
		} 
	}
	
	private function getUserName() {
		return get_current_user();
	}
	
	private function getDirect() {
		
		$this->_rootDirect = dirName(__FILE__)."/../../vhost/";
		
		$this->_apiDirect = $this->_rootDirect.'api/';

		$this->_appDirect = $this->_rootDirect.$this->_appName.'/';
	
	}	
	
	/**
	* @Ttitle 获取app
	*/
	private function getApp($appName = '') {
			
		$this->_appName = $appName;
	}
	
	/**
	* @Title  获取默认的方法：
	*/
	public function getDefaultFunction() {
		
		return ['showList', 'show', 'add', 'update', 'del'];	
	}
	
	/**
	* @Title 生成控制器。module层的名称
	*/
	public function makeName($tableName = '') {
		
		if($tableName == '') {
			
			return;
		}
		
		$theName = $this->getName($tableName);

		$apiConName = $theName."Controller";//生成api控制器的名称
		$modelName = $theName;//生成api module，model层的名称
		$modelInterfaceName = $theName."Interface";
		
		$this->_apiConName        = $apiConName;
		$this->_modelName          = $modelName;
		$this->_modelInterfaceName = $modelInterfaceName;
		return ['apiConName' => $apiConName, 'modelName' => $modelName, 'modelInterfaceName' => $modelInterfaceName];
	}
	
	//生成注释
	private function getNote($type = 0, $func = '') {
		$title = $state = 'none';
		$param = 'param';

		//按照类型生成注释,type = 1 类名，type = 2 方法名
		if ($type === 1) {
			$username = $this->getUserName();
			$date = date('Y-m-d',time());
			$note = "\n/*\n* @Title   none\n* @Author  {$username}\n* @Date    {$date}\n*/";
		} elseif ($type === 2) {
			
			switch ($func) {
				case $func == 'show':
					$title = '根据ID查询详情';
					$param = '$id';
					$state = '数据表ID';
				break;
				case $func == 'showList':
					$title = '根据ID查询列表';
					$param = '$page';
					$state = '页码';
				break;
				case $func == 'add':
					$title = '增加一条记录';
					$param = '$';
					$state = ' ';
				break;
				case $func == 'update':
					$title = '修改记录';
					$param = '$';
					$state = ' ';
				break;
				case $func == 'del':
					$title = '删除记录';
					$param = '$id';
					$state = '数据表ID';
				break;
				default:
					$title = '';
					$param = '$';
					$state = ' ';
			}

			$note = "\n\n\t/**\n\t* @Title\t\t".$title."\n\t* @params \t".$param."\t ".$state."\n\t".$this->iif($func == 'list', '* @params'."\t".'$count'."\t".'查询数量'."\n\t",'');

			for ($i = 0; $i <= 2; $i ++) {
				$note .= "* @params \t$ \n\t";
			}
			
			$note .= "*/";
		}

		return $note;
	}
	
	/**
	* @Title 通过tableName获取名称
	* @note  数据表名必须包括表头zmall_order
	*/
	public function getName($tableName = '') {
		$theName = '';
		$restName = [];

		if($tableName == '') {
			
			return FALSE;
		}

		$name = explode('_', $tableName);

		if(count($name) > 1) {
			
			for($i =1; $i <count($name); $i ++) {
				$restName[] = $name[$i];
				$theName .= ucfirst($name[$i]); 	
			}

			$restName =implode('-', $restName);

			$this->_restName = $restName;
		}

		return $theName;
	}
	
	/*****检测各项文件是否存在**************************/
	
	//检测model文件夹中是否存在目标文件夹并且是否可读写
	private function appExist() {
		
		$appMode = $this->_appDirect;

		if (empty($appModel)) {
			
			return;
		}

		if (!is_dir($appModel)){
			return 1;
		}

		if (is_readable($appModel)) {
			return 0;
		}
	}
	
	//检测分类文件夹是否存在
	private function documentExist() {
		 $docuMode = $this->_appDirect.'models/'.$this->_modelName;
		 //return $docuMode;
			
		if (!is_dir($docuMode)) {
			return 1;
		}

		if (is_readable($docuMode)) {
			return 0;
		}
	}
	
	//监测model文件是否存在
	private function modelExist() {
		
		$docuMode = $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelName.'.php';

		if (!file_exists($docuMode)) {
			return 1;
		}

		if (is_readable($docuMode)) {
			return 0;
		}

	}
	
	//监测model文件是否存在
	private function modelInterfaceExisst() {
		
		$docuMode = $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelInterfaceName.'.php';

		if (!file_exists($docuMode)) {
			return 1;
		}

		if (is_readable($docuMode)) {
			return 0;
		}

	}
	//检测api中的文件是否存在
	private function apiExist() {
		
		$apiFile = $this->_apiDirect.'controllers/'.$this->_apiConName.'.php';	
		
		if (!file_exists($apiFile)) {
			return 1;
		}

		if (is_readable($apiFile)) {
			return 0;
		}
	}
	
	//检测api_model文章是否存在
	private function apiModelExist() {
		$apiFile = $this->_apiDirect.'models/'.$this->_modelName.'.php';

		if (!file_exists($apiFile)) {
			return 1;
		}

		if (is_readable($apiFile)) {
			return 0;
		}
	}
	/*****生成app文件夹，文件夹中的相应文件***********/	
	
	/**
	* @Title 生成app,如果app不存在，则生成app
	*/
	public function makeApp() {
		
		//如果app文件夹已存在且可读写则推出创建
		if($this->appExist() === 0) {
			
			return;
		}
		
		$oldmask = @umask(0);
		$result  = @mkdir($this->_appDirect, 0755, TRUE);
		@umask($oldmask);

		if (!$result) {
			return TRUE;
		} else {
			return FALSE;
		}
	}	
	
	//创建app中的分类文件夹
	public function makeDocument() {
	
		//如果app文件夹已存在且可读写则推出创建
		if($this->documentExist() === 0) {
			
			return;
		}
		$docuMode = $this->_appDirect.'models/'.$this->_modelName;
		$oldmask = @umask(0);
		$result  = @mkdir($docuMode, 0755, TRUE);
		@umask($oldmask);

		if (!$result) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	//生成model接口文件
	public function makeModelInterface() {
		
		$file = $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelInterfaceName.'.php';
		//检测model文件是否存在，如果存在，则重写文件并写入传入的方法，如果不存在，写入传入的方法并写入默认的方法，
		if($this->modelInterfaceExisst() === 0) {
			
			$this->getModelInterfaceClass();
		} else {
			
			$content = "<?php";
			$content .= $this->getNote(1);
			$content .= "\n"."namespace mall\models\ ".$this->_modelName.";\n".'use Yii;';
			$content .= "\nclass {$this->_modelInterfaceName} { ";
			$content .= $this->getModelInterfaceFunc(0, $this->_funcName);
			$content .= "\n}";
		} 		
		
		if (@file_put_contents($file, $content) === FALSE) {
			return FALSE;
		} else {
			
			$oldmask = @umask(0);
			@chmod($model, 0755);
			@umask($oldmask);
			return TRUE;
		}
	}
	
	//生成model文件
	public function makeModel() {
		
		$file = $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelName.'.php';
		//检测model文件是否存在，如果存在，则重写文件并写入传入的方法，如果不存在，写入传入的方法并写入默认的方法，
		if($this->modelExist() === 0) {
			
			$content = $this->getModelClass();
		} else {
		
			$content  = "<?php";
			$content .= $this->getNote(1);
			$content .= "\n"."namespace mall\models\ ".$this->_modelName.";\n".'use Yii;';
			$content .= "\nclass {$this->_modelName} { ";
			$content .= $this->getModelFunc(0, $this->_funcName);
			$content .= "\n}";
		} 		
		
		if (@file_put_contents($file, $content) === FALSE) {
			return FALSE;
		} else {
			
			$oldmask = @umask(0);
			@chmod($model, 0755);
			@umask($oldmask);
			return TRUE;
		}
	}
	
	//如果文件存在，则不创建api文件
	public function makeApi() {
		
		if($this->apiExist() === 0) {
			
			return;
		}
		
		$file = $this->_apiDirect.'controllers/'.$this->_apiConName.'.php';	
		
		$content = "<?php";
		$content .= $this->getNote(1);
		$content .= "\n".'namespace api\controllers;'."\n".'use Yii;'."\n".'use common\library\RenderMessage;'."\n".'use common\components\rest\RestController;';
		$content .= "\nclass {$this->_apiConName} extends RestController { ";
		//$content .= "\n".'public $modelClass = "api\models\'.$this->_modelName.' "';
		$content .= "\n\tpublic \$modelClass = 'api\modelsi\ {$this->_modelName}';";
		$content .= "\n}";

		if (@file_put_contents($file, $content) === FALSE) {
			return FALSE;
		} else {
			
			$oldmask = @umask(0);
			@chmod($model, 0755);
			@umask($oldmask);
			return TRUE;
		}
	}
	
	//生成api model文件,如果文件存在则推出
	public function makeApiModel() {
		
		if($this->apiModelExist() == 0) {
			
			return;
		}
		 
		 $file = $this->_apiDirect.'models/'.$this->_modelName.'.php';
			
		$content = "<?php";
		$content .= $this->getNote(1);
		$content .= "\n".'namespace api\models;'."\n".'use yii\db\ActiveRecord;';
		$content .= "\nclass {$this->_modelName} extends ActiveRecord { ";
		//$content .= "\n".'public $modelClass = "api\models\'.$this->_modelName.' "';
		$content .= "\n\tpublic static function tableName () {\n\t\t return '{$this->_tableName}';\n\t}";
		$content .= "\n\n\tpublic function scenarios () {\n\t\t return [\n\t\t\t'index' => [],\n\t\t\t'view' => ['id'],\n\t\t\t'create' => [],\n\t\t\t'update' => [],\n\t\t\t'delete' => ['id']\n\t\t];\n\t}";
		$content .= "\n\n\tpublic function rules () {\n\t\treturn [\n\t\t];\n\t}";
		$content .= "\n}";

		if (@file_put_contents($file, $content) === FALSE) {
			return FALSE;
		} else {
			
			$oldmask = @umask(0);
			@chmod($model, 0755);
			@umask($oldmask);
			return TRUE;
		}
	}
	
	//获取model接口的方法
	public function getModelInterfaceFunc($flag = 0, $funcName = '') {
		$arr = $this->getDefaultFunction();
		$list = '';

		if($flag === 0) {
			
			foreach ($arr AS $key => $val) {
				$list .= $this->getNote(2, $val);
				$list .= "\n\tpublic function ".$val. "(".$this->eiif($val == 'showList', '$page=1, $count=SUPER_PAGES', $val == 'show' || $val == 'del', '$id=0', '$param = ""').") {\n";
				switch($val) {
					case $val == 'showList':
						$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->showList($page, $count);'."\n\t\t\treturn \$list;";
					break;
					case $val == 'show':
						$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->show($id);'."\n\t\t\treturn \$list; ";

					break;

					case $val == 'del':
						
						$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->show($id);'."\n\t\t\treturn \$list; ";
					break;
				}
				
				$list .= "\n\t}";
			}		

			if (!empty($funcName)){
				$list .= $this->getNote(2, $funcName);
				$list .= "\n\tpublic function ".$funcName."(){";
				$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->'.$funcName.'($id);'."\n\t\t\treturn \$list; ";	
				$list .= "\n\t}";
			}
		} else {
		
			 $class_methods = get_class_methods($this->_modelName);		
			
			if ($funcName) {
				
				if ((in_array($funcName, $class_methods))) {
					return FALSE;
				}
				
				$list .= $this->getNote(2, $funcName);
				$list .= "\n\tpublic function {$funcName}".'($param = "")'." {\n\t}";
			
			}
		
	
		foreach ($arr AS $key => $val) {
			
			if (!(in_array($val, $class_methods))) {
				
				$list .= $this->getNote(2, $val);
				$list .= "\n\tpublic function ".$val. "(".$this->eiif($val == 'showList', '$page=1, $count=SUPER_PAGES', $val == 'show' || $val == 'del', '$id=0', '$param = ""').") {\n";
				switch($val) {
					case $val == 'showList':
						$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->showList($page, $count);'."\n\t\t\treturn \$list;";
					break;
					case $val == 'show':
						$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->show($id);'."\n\t\t\treturn \$list; ";
					break;

					case $val == 'del':
						
						$list .= "\n\t\t\$model = new {$this->_modelName} ();\n\t\t\t\$list  =".'$model->show($id);'."\n\t\t\treturn \$list; ";
					break;
				}
				
					$list .= "\n\t}";
				}
			}
		}
		
		return $list;
	}
	
	//获取model层的类
	public function getModelClass() {
	
		$file = $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelName.'.php';	
		
		if (file_exists($file)) include $file;
		
		$body = '';
		
		//use $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelName;
		
		if (class_exists($this->_modelName, FALSE)) {
			if (!($theFile = fopen($file, "r"))) {
				CoreAjax::ApiCallBack(10002, '无法打开文件');
				return;
			}
			
			while (!feof($theFile)) {
				$arr[] = fgets($theFile);
			}
			
			fclose($theFile);
			
			$count =count($arr);
			
			if($func = $this->getModelFunc($this->_modelName, $this->_funcName, 1)){
				unset($arr[$count-1]);
				//unset($arr[$count-2]);
				$arr[] = $func."\n}";
			}
			return $arr;
		} else {
			$body = "<?php ";
			$body .= "\n".$this->getNote(1);
			$body .= "\n"." namespace mall\models\ ".$this->_modelName.";\n".'use Yii;';
			$body .= "\n".'class '.$this->_modelName."{\n";
			$body .= "\n".'}';
			return $body;
		}
	}
	

	//获取model层的类
	public function getModelInterfaceClass() {
	
		$file = $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelInterfaceName.'.php';	
		
		if (file_exists($file)) include $file;
		
		$body = '';
		
		 //use $this->_appDirect.'models/'.$this->_modelName.'/'.$this->_modelInterfaceName;

		if (class_exists($$this->_modelInterfaceName, FALSE)) {
			
			if (!($theFile = fopen($file, "r"))) {
				return;
			}
			
			while (!feof($theFile)) {
				$arr[] = fgets($theFile);
			}
			
			fclose($theFile);
			
			$count =count($arr);
			
			if($func = $this->getModelInterfaceFunc($this->_modelInterfaceName, $this->_funcName, 1)){
				unset($arr[$count-1]);
				//unset($arr[$count-2]);
				$arr[] = $func."\n}";
			}
			return $arr;
		} else {
			$body = "<?php ";
			$body .= "\n".$this->getNote(1);
			$body .= "\n"."namespace mall\models\ ".$this->_modelName.";\n".'use Yii;';
			$body .= "\n".'class '.$this->_modelInterfaceName."{\n";
			$body .= "\n".'}';
			return $body;
		}
	}
	
	//生成model文件中的方法
	public function getModelFunc($flag = 0, $funcName = '') {
		$arr = $this->getDefaultFunction();
		$list = '';
			
		if($flag === 0) {
			foreach ($arr AS $key => $val) {
				$list .= $this->getNote(2, $val);
				$list .= "\n\tpublic function ".$val. "(".$this->eiif($val == 'showList', '$page=1, $count=SUPER_PAGES', $val == 'show' || $val == 'del', '$id=0', '$param = ""').") {\n";
				switch($val) {
					case $val == 'showList':
						$list .= "\n\t\t\$fields = [\n\t\t\t'page'  => \$page,\n\t\t\t'limit' => \$count\n\t\t];";
						$list .= " \n\n\t\t\$list = RESTful::index('".$this->_restName."', [], \$fields, []);";
						$list .= "\n\n\t\tif (\$list['code'] != 200) {\n\n\t\t\treturn RenderMessage::get(\$list['code'], []);\n\t\t}";
						$list .= "\n\t\treturn RenderMessage::get(200, \$list);";
					break;
					case $val == 'show':
						$list .= "\n\n\t\tif (empty(\$id) || \$id <= 0) {\n\n\t\t\treturn RenderMessage::get(10001);\n\t\t}";
						$list .= "\n\n\t\t\$list = RESTful::view('".$this->_restName."', ['id' => \$id], []);";
						$list .= "\n\n\t\tif (\$list['code'] != 200) {\n\n\t\t\treturn RenderMessage::get(10002, '查询的数据不存在');\n\t\t}";
						$list .= "\n\n\t\treturn  RenderMessage::get(200, \$list);";
					break;

					case $val == 'del':
						$list .= "\n\n\t\t if (empty(\$id) || \$id <= 0) {\n\n\t\t\treturn RenderMessage::get(10001);\n\t\t}";
						$list .= "\n\t\t\$model = RESTful::view ('".$this->_restName."', ['id' => \$id]);";
						$list .= "\n\n\t\tif (\$goods['code'] != 200) {\n\n\t\t\treturn RenderMessage::get(10002, '要删除的商品不存在');\n\t\t}";
						$list .= "\n\n\t\t\$list = RESTful::delete ('".$this->_restName."', \$id);";
						$list .= " \n\n\t\tif (\$list['code'] == 200) {\n\n\t\t\treturn RenderMessage::get(200, '删除成功');\n\t\t} else {\n\n\t\t\treturn RenderMessage::get(10002, '删除失败');\n\t\t}";
					break;
				}
				
				$list .= "\n\t}";
			}
			
			if (!empty($funcName)){
				$list .= $this->getNote(2, $funcName);
				$list .= "\n\tpublic function ".$funcName."(){";
				$list .= "\n";
				$list .= "\n\t}";
			}
		} else {
		
			 $class_methods = get_class_methods($this->_modelName);		
			
			if ($funcName) {
				
				if ((in_array($funcName, $class_methods))) {
					return FALSE;
				}
				
				$list .= $this->getNote(2, $funcName);
				$list .= "\n\tpublic function {$funcName}".'($param = "")'." {\n\t}";
			
			}
			
			foreach ($arr AS $key => $val) {
				if (!(in_array($func, $class_methods))) {
				$list .= $this->getNote(2, $val);
				$list .= "\n\tpublic function ".$val. "(".$this->eiif($val == 'showList', '$page=1, $count=SUPER_PAGES', $val == 'show' || $val == 'del', '$id=0', '$param = ""').") {\n";
				switch($val) {
					case $val == 'showList':
						$list .= "\n\t\t\$fields = [\n\t\t\t'page'  => \$page,\n\t\t\t'limit' => \$count\n\t\t];";
						$list .= " \n\n\t\t\$list = RESTful::index('".$this->_restName."', [], \$fields, []);";
						$list .= "\n\n\t\tif (\$list['code'] != 200) {\n\n\t\t\treturn RenderMessage::get(\$list['code'], []);\n\t\t}";
						$list .= "\n\t\treturn RenderMessage::get(200, \$list);";
					break;
					case $val == 'show':
						$list .= "\n\n\t\tif (empty(\$id) || \$id <= 0) {\n\n\t\t\treturn RenderMessage::get(10001);\n\t\t}";
						$list .= "\n\n\t\t\$list = RESTful::view('".$this->_restName."', ['id' => \$id], []);";
						$list .= "\n\n\t\tif (\$list['code'] != 200) {\n\n\t\t\treturn RenderMessage::get(10002, '查询的数据不存在');\n\t\t}";
						$list .= "\n\n\t\treturn  RenderMessage::get(200, \$list);";
					break;

					case $val == 'del':
							$list .= "\n\n\t\t if (empty(\$id) || \$id <= 0) {\n\n\t\t\treturn RenderMessage::get(10001);\n\t\t}";
							$list .= "\n\t\t\$model = RESTful::view ('".$this->_restName."', ['id' => \$id]);";
							$list .= "\n\n\t\tif (\$goods['code'] != 200) {\n\n\t\t\treturn RenderMessage::get(10002, '要删除的商品不存在');\n\t\t}";
							$list .= "\n\n\t\t\$list = RESTful::delete ('".$this->_restName."', \$id);";
							$list .= " \n\n\t\tif (\$list['code'] == 200) {\n\n\t\t\treturn RenderMessage::get(200, '删除成功');\n\t\t} else {\n\n\t\t\treturn RenderMessage::get(10002, '删除失败');\n\t\t}";
						break;
					}
				
					$list .= "\n\t}";
				}
			}			
		}
		
		return $list;
	}
	
	/***************************消息输出方法**************************/
	private function _getMessage($message = '', $code = 0) {
		
		if($code == '') {
			
			$code == 200;
		}
		
		$code_message = array(
		//常用返回码
			'-1'    => '系统繁忙',
			'0'     => '操作成功',
			'1'     => '系统错误',
			'200'   => '更新成功',
			'10001' => '参数不能为空',
			'10002' => '记录为空',
			'10003' => '格式不正确',
			'10004' => '操作失败',
			'10005' => '数据已存在',
	   
			//发送短信返回码
			'20001' => '获取短信模板参数不能为空',
			'20002' => '号码列表数据错误',
			'20003' => '号码列表数量超限额，必须在小于1000',
			'20004' => '短信内容别表错误',
			'20005' => '定时发送短信时间有误',
			'20006' => '短信内容长度超过限制数量',
			'20007' => '帐户格式不正确(正确的格式为:员工编号@企业编号)',
			'20008' => '服务器拒绝(速度过快、限时或绑定IP不对等)如遇速度过快可延时再发',
			'20009' => '密钥不正确',
			'20010' => '密钥已锁定',
			'20011' => '参数不正确(内容和号码不能为空，手机号码数过多，发送时间错误等)',
			'20012' => '无此帐户',
			'20013' => '帐户已锁定或已过期',
			'20014' => '帐户未开启接口发送',
			'20015' => '不可使用该通道组',
			'20016' => '帐户余额不足',
			'20017' => '内部错误',
			'20018' => '扣费失败',

			//上传图片返回码
			'40001' => '没有图片文件',
			'40002' => '图片类型错误',
			'40003' => '图片大小超过限制',
			'40004' => '图片上传失败',
			'40005' => '图片格式错误',
		);
		
		if(key_exists($code, $code_message) && $message == '') {
			$arr = [$code, $code_message];
		} else {
			$arr = [$code, $message];
		}

		print_r($arr);exit;
	} 
} 
