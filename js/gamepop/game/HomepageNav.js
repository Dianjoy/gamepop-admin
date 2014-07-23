/**
 * Created by meathill on 14-3-28.
 */
;(function (ns) {
  ns.HomepageNav = Backbone.View.extend({
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html().replace(/\s{2,}|\r|\n/g, ''));

      this.list = this.$('ul');
      var spec = this.$el.data();
      this.collection = dianjoy.model.ListCollection.createInstance(null, {
        url: spec.url + '/' + this.model.get('path'),
        id: spec.collectionId
      });
      this.collection.on('reset', this.render, this);
      this.collection.on('add', this.collection_addHandler, this);
      this.collection.on('remove', this.collection_removeHandler, this);
      this.collection.on('change', this.collection_changeHandler, this);
      this.collection.on('sort', this.collection_sortHandler, this);
    },
    remove: function () {
      this.collection.off();
      Backbone.View.prototype.remove.call(this);
      dianjoy.model.ListCollection.destroyInstance(this.collection.url);
    },
    render: function (collection) {
      this.list.width(collection.length * 115);
      this.list.html(this.template({list: collection.toJSON()}));
    },
    createItem: function (model) {
      return this.template({list: [model.toJSON()]});
    },
    collection_addHandler: function (model) {
      this.list.append(this.createItem(model));
    },
    collection_changeHandler: function (model) {
      var item = this.$('#nav-' + ('id' in model.changed ? model.cid : model.id));
      if ('status' in model.changed) {
        item.toggleClass('hide', model.get('status'));
        return;
      }
      if (item.length) {
        item.replaceWith(this.createItem(model));
      } else {
        this.list.append(this.createItem(model));
      }
    },
    collection_removeHandler: function (model) {
      this.$('#nav-' + model.id).fadeOut(function () {
        $(this).remove();
      });
    },
    collection_sortHandler: function (model, index) {
      var item = this.$('#nav-' + model.id);
      item = item.length > 0 ? item : this.$('#nav-' + model.cid);
      if (item.index() < index) {
        item.insertAfter(this.list.children().eq(index));
      } else {
        item.insertBefore(this.list.children().eq(index));
      }
    }
  });
}(Nervenet.createNameSpace('gamepop.game')));