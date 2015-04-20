(function($){
  // 定义辅助方法
  $.helper = {
    domain : "http://" + (document.domain == '' ? 'wxqy.dev' : document.domain),
     api : "http://" + (document.domain == '' ? 'wxqy.dev' : document.domain) + "/api",
    upyunDomain : "http://wxqy.b0.upaiyun.com",
    url: function(path){
      var url = '';
       url = this.domain + "/" + path;
      return url;
    },
    asset : function(path) {
      var url = '';
      if (path == undefined) {
        url = this.domain;
      } else {
        url = this.domain + "/" + path;
      }
      return url;
    },
    upyun : function(path) {
      var url = '';
      if ($isLocal) {
        url = this.domain + "/" + path;
      } else {
        url = this.upyunDomain + "/" + path;
      }
      return url;
    },
    show: function($dom) {
      return $dom.removeClass('hidden').removeClass('hide').show();
    },
    hide: function($dom) {
      return $dom.removeClass('show').hide();
    },
    fadeIn: function($dom, $speed) {
      if ($speed == undefined) {
        $speed = 'normal';
      }
      $dom.fadeIn($speed).removeClass('hidden').removeClass('hide');
    },
    fadeOut: function($dom, $speed) {
      if ($speed == undefined) {
        $speed = 'normal';
      }
      $dom.removeClass('hidden').removeClass('hide').fadeOut($speed);
    },
    arrGet: function($arr, $column, $id) {
      var $item;
      $.each($arr, function(k, $val) {
        if ($val[$column] == $id) {
          $item = $val;
          return false;
        }
      });
      return $item;
    },
    alert: function($text, $type, $isAutoHide) {
      // 自定义alert
      var $tips = $('#alert-tips'),
          $textDom = $tips.find('.inner-text')
          $classText = 'bg-primary bg-error';
      $textDom.html($text);
      $textDom.removeClass($classText);
      $type = ($type == undefined) ? 1 : $type ;
      $isAutoHide = ($isAutoHide == undefined) ? true : $isAutoHide ;
      if ($type == 1) {
        // primary alert
        $textDom.addClass('bg-primary');
      } else if ($type == 2) {
        // error alert
        $textDom.addClass('bg-error');
      } else {
        $textDom.addClass('bg-primary');
      }
      $.helper.fadeIn($tips);
      if ($isAutoHide != false) {
        setTimeout(function(){
          $tips.fadeOut();
        }, 5000);
      }
    },
    int2Chinese : function ($num) {
      // 将阿拉伯数字转换成中文数字
      var ary0 = ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九"],
        ary1 =["", "十", "百", "千"],
        ary2 = ["", "万", "亿", "兆"],
        zero = "",
        newary = "",
        i4 = -1;

      if (! isNaN($num)) {
        // 不是数字
        $num = $num.toString();
      }
      // 倒转字符串。
      var $ary = [];
      for (var i = $num.length; i >= 0; i--) {
        $ary.push($num[i])
      }
      $ary = $ary.join("");

      // 处理
      for (var i = 0; i < $ary.length; i++) {
        if (i % 4 == 0) { //首先判断万级单位，每隔四个字符就让万级单位数组索引号递增
          i4++;
          newary = ary2[i4] + newary; //将万级单位存入该字符的读法中去，它肯定是放在当前字符读法的末尾，所以首先将它叠加入$r中，
          zero = ""; //在万级单位位置的“0”肯定是不用的读的，所以设置零的读法为空

        }
        //关于0的处理与判断。
        if ($ary[i] == '0') { //如果读出的字符是“0”，执行如下判断这个“0”是否读作“零”
          switch (i % 4) {
            case 0:
              break;
              //如果位置索引能被4整除，表示它所处位置是万级单位位置，这个位置的0的读法在前面就已经设置好了，所以这里直接跳过
            case 1:
            case 2:
            case 3:
              if ($ary[i - 1] != '0') {
                zero = "零"
              }; //如果不被4整除，那么都执行这段判断代码：如果它的下一位数字（针对当前字符串来说是上一个字符，因为之前执行了反转）也是0，那么跳过，否则读作“零”
              break;

          }

          newary = zero + newary;
          zero = '';
        } else { //如果不是“0”
          newary = ary0[parseInt($ary[i])] + ary1[i % 4] + newary; //就将该当字符转换成数值型,并作为数组ary0的索引号,以得到与之对应的中文读法，其后再跟上它的的一级单位（空、十、百还是千）最后再加上前面已存入的读法内容。
        }

      }
      if (newary.indexOf("零") == 0) {
        newary = newary.substr(1)
      } //处理前面的0
      return newary;
    }
  };
  // 定义共有变量
  $.cvar = {
    datatable: {
      config: {
        pageLength: 20,
        lengthChange: false,
        language: {
          search: '搜索：',
          searchPlaceholder: '请输入.....',
          emptyTable: '无记录',
          info: '显示_START_到_END_，共_TOTAL_行',
          infoEmpty: '',
          infoFiltered: '从_MAX_行记录中搜索',
          zeroRecords: '搜索无记录',
          paginate: {
            first: '首页',
            last: '最后一页',
            next: '下一页',
            previous: '上一页',
          },
        },
      }
    }
  };
})(window.jQuery || window.Zepto);

(function($){
  var func = {
    customAjax : function(options){
      // 快速ajax函数
      _beforePost = function($form){
        return $form.settings.beforePost($form);
      },
      _beforeSend = function($form){
        return $form.settings.beforeSend($form);
      },
      _success = function ($form, $data) {
        $form.settings.success($form, $data);
      },
      _post = function($form) {
        if (! _beforePost($form)) {
          return false;
        }
        if ($form.attr('method') != '') {
          $form.settings.type = $form.attr('method');
        }
        if ($form.settings.fileUpload && $form.data('file') != undefined) {
          var $file = $form.data('file');
          $form.find('.input-file').fileupload(
            'option',
            'type',
            $form.settings.type
          );
          $form.find('.btn-submit').button('loading');
          $file.submit().always(function() {
            $form.find('.btn-submit').button('reset');
          });
          return;
        }
        $.ajax({
          type: $form.settings.type,
          url: $form.attr('action'),
          dataType: 'json',
          data: $form.serialize(),
          beforeSend: function() {
            _beforeSend($form);
          },
          success: function($data) {
            _success($form, $data);
          },
          complete: function() {
            _complete($form);
          },
          error: function() {
            _error($form);
          }
        })
      },
      _complete = function($form) {
        $form.settings.complete($form);
      },
      _error = function(){
        $.helper.alert('系统错误，请稍后再试', 2);
      };

      return this.each(function(){
        var $this = $(this);
        var settings = {
          type : 'post',
          fileUpload: false,
          fileUploadOpts: {
            url: $this.attr('action'),
            //forceIframeTransport: true,
            //iframe: true,
            type: 'post',
            dataType: 'json',
            autoUpload: false,
            formAcceptCharset: 'utf-8',
            singleFileUploads: true,
            acceptFileTypes: /(\.|\/)(pdf)$/i,
            maxFileSize: 5000000, // 5 MB
            // Enable image resizing, except for Android and Opera,
            // which actually support image resizing, but fail to
            // send Blob objects via XHR requests:
            disableImageResize: /Android(?!.*Chrome)|Opera/
              .test(window.navigator.userAgent),
          },
          fileUploadFunc: {
            fileuploadadd: function(){},
            fileuploadstart: function($form, $e) {
              // 开始上传
              $.helper.show($form.find('.progress'));
              $form.find('.progress .progress-bar').css('width', 0);
            },
            fileuploadprogressall: function($form, $e, $data) {
              // 所有文件正在上传时
              var progress = parseInt($data.loaded / $data.total * 100, 10);
              $form.find('.progress .progress-bar').css(
                'width',
                progress + '%'
              );
            },
            fileuploaddone: function($form, $e, $data) {
              // 文件上传完毕
              if ($data.result['c'] == 0) {
                $.helper.alert($data.result['mesg'], 1);
              } else {
                $.helper.alert($data.result['mesg'], 2);
              }
            },
            fileuploadalways: function($form, $e, $data) {
              setTimeout(function(){
                $.helper.fadeOut($form.find('.progress'));
              }, 5000);
            },
            fileuploadfail: function($form, $e, $data) {
              // 文件上传失败
              $.helper.alert('网络错误，请重新操作', 2);
            },
          },
          beforePost : function($form){return true},
          beforeSend : function($form){
            $form.find('.btn-submit').button('loading');
          },
          success : function($form, $data) {
            if ($data['errcode'] == 0) {
              $.helper.alert('操作成功', 1);
            } else {
              $.helper.alert($data['errmsg'], 2);
            }
          },
          complete: function($form) {
            $form.find('.btn-submit').button('reset');
          },
        };
        if (options != undefined) {
            if (options.fileUploadOpts != undefined) {
                options.fileUploadOpts = $.fn.extend(settings.fileUploadOpts, options.fileUploadOpts);
            }
            if (options.fileUploadFunc != undefined) {
                options.fileUploadFunc = $.fn.extend(settings.fileUploadFunc, options.fileUploadFunc);
            }
        }
        $this.settings = $.fn.extend(settings, options);
        if ($this.settings.fileUpload) {
          // 附件上传js绑定
          $this.find('.input-file').fileupload($this.settings.fileUploadOpts);
          $.each($this.settings.fileUploadFunc, function($name, $func) {
            $this.find('.input-file')
              .on($name, function(e, data) {
                $func($this, e, data);
              });
          });
          $this.find('.input-file')
            .prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
        }
        $this.submit(function(e){
          e.preventDefault();
          _post($this);
        });

        return $this;
      });
    }
  };
  if ($.fn.extend == undefined) {
    $.extend(func);
  } else {
    $.fn.extend(func);
  }
})(window.jQuery || window.Zepto)

