/**
 * Created by meathill on 14-3-19.
 */
var baseURL = "api/"
  , GAid = "UA-35957679-12"
  , webURL = "web/"
  , custom = "js/";

// 上传路径等配置
dianjoy.service.Manager.autoUpload = true;
dianjoy.service.Manager.api = './upload.php';
dianjoy.service.Manager.maxUploadSize = 209715200;