/**
 * Created by meathill on 14-3-17.
 */
;(function (ns) {
  var defaults = {
    icon_path: 'img/image.png',
    game_name: '<i class="fa fa-spin fa-spinner"></i> ',
    game_desc: '加载中，请稍后',
    guide_name: ''
  };
  ns.GameInfo = Backbone.View.extend({
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());
      this.model.urlRoot = 'api/games/info.php';
      this.model.once('sync', this.render, this);
      this.model.fetch();
      this.render(defaults);
    },
    render: function (model) {
      if (model instanceof Backbone.Model) {
        model = model.toJSON();
      }
      this.$el.html(this.template(model));
    }
  });
}(Nervenet.createNameSpace('dianjoy.game')));