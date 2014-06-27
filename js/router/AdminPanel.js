;(function (ns) {
  'use strict';
  var regs = [
    /^(game|category|artcile|author)(\w+)$/,
    /^(p)(\d+)$/,
    /^(keyword)-([\u4E00-\u9FA5\w]+)$/
  ];
  ns.AdminPanel = dianjoy.router.BaseRouter.extend({
    routes: {
      '': 'showHomepage',
      'admin/:sub': 'showAdminPage',
      ':cate/:sub(/*path)': 'showNormalPage'
    },
    showNormalPage: function (cate, sub, path) {
      var data = {
        cate: cate,
        sub: sub
      };
      if (path) {
        var params = path.split('/');
        for (var i = 0, len = params.length, isFind = false; i < len; i++) {
          isFind = false
          for (var j = 0, jlen = regs.length; j < jlen; j++) {
            var arr = regs[j].exec(params[i]);
            if (arr) {
              data[arr[1]] = arr[2];
              isFind = true;
              break;
            }
          }
          if (!isFind) {
            data.id = params[i];
          }
        }
      }
      if (!this.diff(data)) {
        return;
      }
      this.params = data;
      var url = baseURL + cate + '/template/' + sub + '.html';
      this.$subPage.load(url, data);
    },
    showAdminPage: function (sub) {
      var url = baseURL + '/admin/' + sub + '.php';
      this.$subPage.load(url);
    }
  });
})(Nervenet.createNameSpace('dianjoy.router'));
