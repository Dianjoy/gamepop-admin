;(function (ns) {
  'use strict';
  var reg = /\/?(game|category|artcile|author)(\w+)/
    , mediatorInit = {};
  mediatorInit.article = {
    guide_name: '',
    category: 0,
    label: '',
    source: '',
    topic: '',
    author: '您',
    icon_path: './img/image.png',
    pub_date: '',
    src_url: ''
  };
  ns.AdminPanel = dianjoy.router.BaseRouter.extend({
    routes: {
      '': 'showHomepage',
      'admin/:sub': 'showAdminPage',
      ':cate/:sub(/*path)': 'showNormalPage'
    },
    showNormalPage: function (cate, sub, path) {
      var data = cate in mediatorInit ? _.extend({}, mediatorInit[cate]) : {};
      if (path) {
        var params = path.split('/');
        for (var i = 0, len = params.length; i < len; i++) {
          var arr = reg.exec(params[i]);
          if (arr) {
            data[arr[1] !== 'game' ? arr[1] : 'id'] = arr[2];
          } else {
            data.id = params[i];
          }
        }
      }
      var url = baseURL + cate + '/template/' + sub + '.html';
      this.$subPage.load(url, data, path);
    },
    showAdminPage: function (sub) {
      var url = baseURL + '/admin/' + sub + '.php';
      this.$subPage.load(url);
    }
  });
})(Nervenet.createNameSpace('dianjoy.router'));
