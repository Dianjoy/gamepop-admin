/**
 * Created by meathill on 14-4-22.
 */
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
      'outsider/:sub(/*path)': 'showNormalPage',
      ':cate(/*path)': 'showErrorPage'
    },
    showHomepage: function () {
      this.params = {};
      this.navigate('#/outsider/list');
      var url = baseURL + 'outsider/template/list.html';
      this.$subPage.load(url);
    },
    showNormalPage: function (sub, path) {
      var data = {
        cate: 'outsider',
        sub: sub
      };
      if (path) {
        var params = path.split('/');
        for (var i = 0, len = params.length, isFind = false; i < len; i++) {
          isFind = false;
          for (var j = 0, jlen = regs.length; j < jlen; j++) {
            var arr = regs[j].exec(params[i]);
            if (arr) {
              data[arr[1] === 'p' ? 'page' : arr[1]] = arr[2];
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
      var url = baseURL + 'outsider/template/' + sub + '.html';
      this.$subPage.load(url, data);
    },
    showErrorPage: function () {
      this.$subPage.load(baseURL + 'outsider/template/permission-error.html');
    }
  });
}(Nervenet.createNameSpace('dianjoy.router')));