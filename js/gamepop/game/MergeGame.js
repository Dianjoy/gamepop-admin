/**
 * Created by meathill on 14-7-3.
 */
;(function (ns) {
  'use strict';

  var Collection = Backbone.Collection.extend({
    url: 'api/game/list.php',
    total: 0,
    parse: function (response) {
      this.total = response.total;
      return response.list;
    }
  });

  ns.MergeGame = Backbone.View.extend({
    events: {
      'change .item': 'item_changeHandler',
      'click .search-button': 'searchButton_clickHandler',
      'keydown input': 'input_keyDownHandler'
    },
    initialize: function () {
      this.template = Handlebars.compile(this.$('script').remove().html());

      this.collection = new Collection();
      this.collection.on('reset', this.collection_resetHandler, this);
    },
    remove: function () {
      this.collection.off();
      this.collection = null;
      Backbone.View.prototype.remove.call(this);
    },
    search: function (input) {
      this.target = input.attr('name');
      var keyword = input.val();
      this.collection.fetch({
        reset: true,
        data: {
          keyword: keyword,
          pagesize: 20,
          guide_from: this.target === 'from' ? '' : '4399',
          from: 'merge'
        }
      });
      input.prop('disabled', true)
        .next().find('button').prop('disabled', true);
    },
    collection_resetHandler: function (collection) {
      var data;
      if (collection.total === 0) {
        data = {};
      } else {
        var data = collection.toJSON();
        for (var i = 0, len = data.length; i < len; i++) {
          data[i].name = this.target;
        }
        data = {list: data};
      }
      this.$('.' + this.target).html(this.template(data));
      this.$('[name=' + this.target + ']').prop('disabled', false)
        .next().find('button').prop('disabled', false);
    },
    input_keyDownHandler: function (event) {
      if (event.keyCode === 13 && event.target.value !== '') {
        this.search($(event.target));
        event.preventDefault();
      }
    },
    item_changeHandler: function () {
      var ready = this.$('[name=from]:checked').length && this.$('[name=to]:checked').length;
      this.$('.btn-primary').prop('disabled', !ready);
    },
    searchButton_clickHandler: function (event) {
      var input = $(event.currentTarget).parent().prev();
      if (input.val() !== '') {
        this.search(input);
      }
    }
  });
}(Nervenet.createNameSpace('gamepop.game')));