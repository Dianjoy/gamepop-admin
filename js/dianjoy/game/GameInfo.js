/**
 * Created by meathill on 14-3-17.
 */
;(function (ns) {
  var Model = Backbone.Model.extend({
    defaults: {
      icon_path: 'img/image.png',
      game_name: '<i class="fa fa-spin fa-spinner"></i> ',
      game_desc: '加载中，请稍后',
      guide_name: ''
    },
    urlRoot: 'api/games/info.php'
  });
  ns.GameInfo = Backbone.View.extend({
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());
      var hash = location.hash
        , id = hash.substr(hash.lastIndexOf('/') + 1);
      this.model = new Model({
        id: id
      });
      this.model.on('sync', this.render, this);
      this.model.fetch();
      this.render(this.model);
    },
    render: function (model) {
      this.$el.html(this.template(model.toJSON()));
    }
  });
}(Nervenet.createNameSpace('dianjoy.game')));