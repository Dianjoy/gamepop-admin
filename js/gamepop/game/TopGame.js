/**
 * Created by meathill on 14-6-6.
 */
;(function (ns) {
  ns.TopGame = Backbone.View.extend({
    entrance: null,
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());
      this.collection = dianjoy.model.ListCollection.createInstance(null, {
        url: this.$el.data('url')
      });
      this.collection.on('reset', this.render, this);
      this.collection.on('add', this.collection_addHandler, this);
      this.collection.on('change', this.collection_changeHandler, this);
      this.collection.on('sort', this.collection_sortHandler, this);
    },
    render: function () {
      if (this.collection.length === 0) {
        return;
      }
      var model = this.collection.at(0);
      if (model.get('big_pic')) {
        this.$el.css('background-image', 'url(' + model.get('big_pic') + ')');
      }
      if (model.get('logo')) {
        if (this.entrance) {
          this.entrance.remove();
        }
        this.entrance = $(this.template(model.toJSON()));
        this.$('#homepage-search').append(this.entrance);
      }
    },
    collection_addHandler: function (model) {
      if (this.collection.indexOf(model) === 0) {
        this.render();
      }
    },
    collection_changeHandler: function () {
      this.render();
    },
    collection_sortHandler: function () {
      this.render();
    }
  });

}(Nervenet.createNameSpace('gamepop.game')));