;(function (ns) {
  'use strict';
  var reg = /\/?(game|category|artcile|author)(\w+)/g;
  ns.AdminPanel = Backbone.Router.extend({
    $mainPage: null,
    $subPage: null,
    lastPage: null,
    routes: {
      '': 'showHomepage',
      'admin/:sub': 'showAdminPage',
      ':cate/:sub(/*path)': 'showNormalPage'
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
      var url = baseURL + 'dashboard/';
      this.$subPage.load(url);
    },
    showNormalPage: function (cate, sub, path) {
      if (path) {
        var arr = reg.exec(path)
          , data = {};
        if (arr) {
          while(arr) {
            data[arr[1]] = arr[2];
            arr = reg.exec(path);
          }
        } else {
          data = path;
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
