var url = 'http://relay.ramos.seafarer.me';

// 获取 jssdk 签名
// $.ajax({
//   url: url + '/wx/api/jssdk/signpackage',
//   type: 'GET',
//   dataType: 'json',
//   success: function (data) {
//     console.log(data);
//     wx.config({
//       debug: true,
//       appId: data.sign.appId,
//       timestamp: data.sign.timestamp,
//       nonceStr: data.sign.nonceStr,
//       signature: data.sign.signature,
//       jsApiList: ['chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'startRecord', 'stopRecord', 'onVoicePlayEnd', 'uploadVoice', 'downloadVoice']
//     })
//   },
//   error: function (error) {
//     console.log(error);
//   }
// });
var coordinate = {
  '1': {
    key: 'baoji',
    past: 0,
    remain: 4868,
    distance: 0,
    name: '宝鸡',
    is_middle: false,
    x: '46%',
    y: 75
  },
  '2': {
    key: 'baoji',
    past: 418,
    remain: 4450,
    distance: 418,
    name: '宝鸡',
    is_middle: true,
    x: '40%',
    y: 83
  },
  '3': {
    key: 'xining',
    past: 721,
    remain: 4147,
    distance: 303,
    name: '西宁',
    is_middle: false,
    x: '33%',
    y: 95
  },
  '4': {
    key: 'xining',
    past: 2070,
    remain: 2798,
    distance: 1349,
    name: '西宁',
    is_middle: true,
    x: '41%',
    y: 108
  },
  '5': {
    key: 'huatugou',
    past: 3047,
    remain: 1821,
    distance: 977,
    name: '花土沟',
    is_middle: false,
    x: '45%',
    y: 126
  },
  '6': {
    key: 'huatugou',
    past: 3305,
    remain: 1563,
    distance: 258,
    name: '花土沟',
    is_middle: true,
    x: '53%',
    y: 137
  },
  '7': {
    key: 'jingyuhu',
    past: 3493,
    remain: 1375,
    distance: 188,
    name: '鲸鱼湖',
    is_middle: false,
    x: '61%',
    y: 143
  },
  '8': {
    key: 'jingyuhu',
    past: 3591,
    remain: 1277,
    distance: 98,
    name: '鲸鱼湖',
    is_middle: true,
    x: '64%',
    y: 155
  },
  '9': {
    key: 'taiyanghu',
    past: 3663,
    remain: 1205,
    distance: 72,
    name: '太阳湖',
    is_middle: false,
    x: '57%',
    y: 174
  },
  '10': {
    key: 'taiyanghu',
    past: 3740,
    remain: 1128,
    distance: 77,
    name: '太阳湖',
    is_middle: true,
    x: '48%',
    y: 184
  },
  '11': {
    key: 'xiangyanghu',
    past: 3797,
    remain: 1071,
    distance: 57,
    name: '向阳湖',
    is_middle: false,
    x: '38%',
    y: 192
  },
  '12': {
    key: 'xiangyanghu',
    past: 3854,
    remain: 1014,
    distance: 57,
    name: '向阳湖',
    is_middle: true,
    x: '40%',
    y: 206
  },
  '13': {
    key: 'wuquanhe',
    past: 3897,
    remain: 971,
    distance: 43,
    name: '五泉河',
    is_middle: false,
    x: '41%',
    y: 219
  },
  '14': {
    key: 'wuquanhe',
    past: 3983,
    remain: 885,
    distance: 86,
    name: '五泉河',
    is_middle: true,
    x: '46%',
    y: 228
  },
  '15': {
    key: 'dongwenhe',
    past: 4046,
    remain: 822,
    distance: 63,
    name: '东温河',
    is_middle: false,
    x: '50%',
    y: 232
  },
  '16': {
    key: 'dongwenhe',
    past: 4146,
    remain: 722,
    distance: 100,
    name: '东温河',
    is_middle: true,
    x: '44%',
    y: 243
  },
  '17': {
    key: 'shuanghu',
    past: 4219,
    remain: 649,
    distance: 73,
    name: '双湖',
    is_middle: false,
    x: '38%',
    y: 246
  },
  '18': {
    key: 'shuanghu',
    past: 4595,
    remain: 273,
    distance: 376,
    name: '双湖',
    is_middle: true,
    x: '28%',
    y: 251
  },
  '19': {
    key: 'lasa',
    past: 4868,
    remain: 0,
    distance: 273,
    name: '拉萨',
    is_middle: false,
    x: '20%',
    y: 269
  }
};

function jump (num) {
  var l = coordinate[num];

  // 文案
  $('.js-name').text(l.name);
  $('.js-past').text(l.past);
  $('.js-progress-number').text(l.past + 'km');
  $('.js-remain').text(l.remain);

  // 进度条
  var p = (l.past) / 4868 * 100 + '%';
  $('.js-progress').width(p);

  // 位置
  $('.js-spin').css({
    bottom: l.y,
    left: l.x
  });
};

// 邀请好友接力按钮
$('.js-invite-btn').tap(function () {
  $('.js-share').show();
});

// 找伙计帮忙按钮
$('.js-help-btn').tap(function () {
  $('.js-share').show();
})

// 为他接力按钮
$('.js-friend-btn').tap(function () {
  $.ajax({
    url: url + '/wx/api/click',
    type: 'POST',
    dataType: 'json',
    data: {suid: 1},
    success: function (data) {
      console.log(data);
      window.location = $.helper.url('wx/page/click/result');
      // 页面跳转
    },
    error: function (error) {
      console.log(error);
    }
  })
})
// 炫耀一下
$('.js-shine-btn').tap(function () {
  $('.js-share').show();
})

// 领取梦想基金
$('.js-success-btn').tap(function () {
  // body...
})

// 关闭分享弹框
$('.js-share').tap(function () {
  $(this).hide();
})
