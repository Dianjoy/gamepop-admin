/**
 * Created by meathill on 14-4-22.
 */
;(function (ns) {
  'use strict';
  var reg = /\/?(game|category|artcile|author)(\w+)/;
  ns.AdminPanel = Backbone.Router.extend({
    $mainPage: null,
    $subPage: null,
    lastPage: null,
    routes: {
      '': 'showHomepage',
      'outsider/:sub(/*path)': 'showNormalPage',
      ':cate(/*path)': 'showErrorPage'
    },
    execute: function (callback, args) {
      if (location.hash === this.lastPage) {
        return;
      }
      if (this.$subPage.preCheck()) {
        this.lastPage = location.hash;
        callback.apply(this, args);
      } else {
        this.navigate(this.lastPage, {trigger: false, replace: true});
      }
    },
    showHomepage: function () {
      var url = baseURL + 'outsider/template/list.html';
      this.$subPage.load(url);
    },
    showNormalPage: function (sub, path) {
      if (path) {
        var params = path.split('/')
          , data = {};
        for (var i = 0, len = params.length; i < len; i++) {
          var arr = reg.exec(params[i]);
          if (arr) {
            data[arr[1] !== 'game' ? arr[1] : 'id'] = arr[2];
          } else {
            data.id = params[i];
          }
        }
      }
      var url = baseURL + 'outsider/template/' + sub + '.html';
      this.$subPage.load(url, data, path);
    },
    showErrorPage: function () {
      this.$subPage.load(baseURL + 'outsider/template/permission-error.html');
    }
  });
}(Nervenet.createNameSpace('dianjoy.router')));