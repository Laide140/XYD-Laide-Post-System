<?php
require_once('config.php');

$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);
$userimg = "./unkown.webp";

if (isset($_COOKIE["username"])) {
    $username = $_COOKIE["username"];
    $userlogin = true;
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_COOKIE["username"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    if ($user_data) {
        $userct = $user_data['create_time'];
        $userut = $user_data['update_time'];
        $userrole = $user_data['role'];
        $userpost = $user_data['post'];
        $postadmin = $user_data['postadmin'];
        $enableQPT = $user_data['enableQPT'];
        $enableOS = $user_data['enableOS'];
        $userimg = $user_data['headimg'] ?? './unkown.webp';
    } else {
        $userct = $userut = $userrole = $userpost = $postadmin = $enableQPT = $enableOS = "";
    }
} else {
    $userpost = "";
    $username = "未登录";
    $userlogin = false;
}

$refresh_page = isset($_GET['refresh']) && $_GET['refresh'] == 'true';

$sql = "SELECT * FROM users WHERE mainpost = ? ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userpost);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>新一代营业渠道系统</title>
  <link rel="stylesheet" href="./layui/css/layui.css">
  <style>
    @font-face {
        font-family: "LFangSong";
        src: url("./fonts/ChangFangSong.ttf");
    }
    @font-face {
        font-family: "ArialNarrow";
        src: url("./fonts/ArialNarrow.ttf");
    }
    .disabled-link {
      color: #999 !important;
      cursor: not-allowed !important;
    }
    .disabled-link:hover {
      color: #999 !important;
    }
    label.required:after {
        content: ' *';
        color: red;
    }
    .tracking-result {
      margin-top: 20px;
      padding: 15px;
      background: #f8f8f8;
      border-radius: 4px;
    }
    .tracking-item {
      padding: 8px 0;
      border-bottom: 1px solid #eee;
    }
    .tracking-item:last-child {
      border-bottom: none;
    }
    .tracking-time {
      color: #666;
      font-size: 12px;
    }
    .tracking-desc {
      margin-top: 5px;
    }
    /* 新增建议下拉框样式 */
    .phone-suggest-container {
      position: absolute;
      background: white;
      border: 1px solid #d2d2d2;
      border-radius: 2px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      z-index: 9999;
      max-height: 200px;
      overflow-y: auto;
      width: 300px;
    }
    
    .phone-suggest-item {
      padding: 8px 10px;
      cursor: pointer;
      border-bottom: 1px solid #f2f2f2;
      font-size: 13px;
    }
    
    .phone-suggest-item:hover {
      background-color: #f2f2f2;
    }
    
    .phone-suggest-item small {
      color: #999;
      margin-left: 10px;
    }
    
    .btn {
    border-radius: 15.5px;
    width: 51px;
    height: 31px;
    background-color: #e9e9eb;
}

.ios-switch {
    position: relative;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    transition: all 100ms;
    border-radius: 31px;
    width: 51px;
    height: 31px;
    background-color: #e9e9eb;
}

.ios-switch::before {
    position: absolute;
    content: "";
    transition: all 300ms cubic-bezier(.45, 1, .4, 1);
    border-radius: 15.5px;
    width: 51px;
    height: 31px;
    background-color: #e9e9eb;
}

.ios-switch::after {
    position: absolute;
    left: 2px;
    top: 2px;
    border-radius: 27px;
    width: 27px;
    height: 27px;
    background-color: #fff;
    box-shadow: 1px 1px 5px rgba(0, 0, 0, .3);
    content: "";
    transition: all 300ms cubic-bezier(.4, .4, .25, 1.35);
}

.ios-switch:checked {
    background-color: #5eb631;
}

.ios-switch:checked::before {
    transform: scale(0);
}

.ios-switch:checked::after {
    transform: translateX(20px);
}
  </style>
  <script src="./layui/layui.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <script src="https://unpkg.com/@zxing/library@0.20.0/umd/index.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
  let isScanning = false;
let codeReader = null;
let scannerModal = null;
let currentInputId = null;

// 创建模态框（动态创建，避免依赖外部HTML）
function createScannerModal() {
  if (document.getElementById('scannerModal')) return;
  
  const modal = document.createElement('div');
  modal.id = 'scannerModal';
  modal.style.cssText = `
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  `;
  
  const content = document.createElement('div');
  content.style.cssText = `
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    width: 90%;
    max-width: 500px;
    position: relative;
  `;
  
  content.innerHTML = `
    <div style="position: relative;">
      <video id="scanner-video" style="width: 100%; max-height: 400px; object-fit: cover;"></video>
      <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; height: 2px; background: rgba(255,0,0,0.6); box-shadow: 0 0 5px red;"></div>
      <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 2px solid rgba(255,255,255,0.3); pointer-events: none;"></div>
    </div>
    <div style="padding: 12px; text-align: center; background: #f8f8f8;">
      <p style="margin: 5px 0; color: #333;">将二维码/条形码放入框内自动识别</p>
      <button id="closeScannerBtn" style="padding: 8px 20px; background: #FF5722; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">关闭</button>
    </div>
  `;
  
  modal.appendChild(content);
  document.body.appendChild(modal);
}

// 重写后的 startRealScan 函数
async function startRealScan(inputId) {
  if(isScanning) return;
  
  // 保存当前输入框ID
  currentInputId = inputId;
  
  // 创建模态框
  createScannerModal();
  scannerModal = document.getElementById('scannerModal');
  scannerModal.style.display = 'flex';
  
  try {
    codeReader = new ZXing.BrowserMultiFormatReader();
    const devices = await codeReader.listVideoInputDevices();
    if(devices.length === 0) throw new Error('无摄像头');
    
    let deviceId = devices[0].deviceId;
    for(let d of devices){
      if(d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear')){
        deviceId = d.deviceId;
        break;
      }
    }
    
    await codeReader.decodeFromVideoDevice(deviceId, 'scanner-video', (result, err) => {
      if(result){
        const text = result.getText();
        // 找到对应的输入框并修改其 value
        const targetInput = document.getElementById(currentInputId);
        if(targetInput){
          targetInput.value = text;
          // 触发input事件，以便其他监听器能感知变化
          targetInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        // 显示成功提示
        if(window.layer){
          layer.msg('已填入: ' + text, { icon: 1, time: 1500 });
        } else {
          alert('已填入: ' + text);
        }
        closeScanner();
      }
    });
    isScanning = true;
  } catch(e){
    alert('无法启动摄像头：' + (e.message || '请检查权限'));
    closeScanner();
  }
  
  // 绑定关闭按钮事件
  setTimeout(() => {
    const closeBtn = document.getElementById('closeScannerBtn');
    if(closeBtn){
      closeBtn.onclick = closeScanner;
    }
  }, 100);
}

// 关闭扫描器函数
async function closeScanner(){
  isScanning = false;
  if(codeReader){
    try{
      await codeReader.reset();
    } catch(e){}
    codeReader = null;
  }
  const video = document.getElementById('scanner-video');
  if(video && video.srcObject){
    video.srcObject = null;
  }
  if(scannerModal){
    scannerModal.style.display = 'none';
  }
}
  var isLoggedIn = <?php echo $userlogin ? 'true' : 'false'; ?>;
  var pageContents = {};
  var currentPage = '';

  function checkLogin(pageName) {
    currentPage = pageName;
    if (!isLoggedIn) {
      layui.use('layer', function(){
        var layer = layui.layer;
        layer.confirm('请先登录才能访问 ' + pageName + ' 功能', {
          title: '登录提示',
          icon: 3,
          btn: ['去登录', '取消'],
          yes: function(index) {
            showLogin();
            layer.close(index);
          },
          btn2: function(index) {
            layer.close(index);
          }
        });
      });
      return false;
    }
    loadPageContent(pageName);
    return true;
  }

  function loadPageContent(pageName) {
    $('#mainContent').html('<div class="layui-card"><div class="layui-card-header">' + pageName + '</div><div class="layui-card-body">' + pageContents[pageName] + '</div></div>');
    
    layui.use(['form'], function(){
      var form = layui.form;
      form.render('select');
      form.render();
    });
    
    setTimeout(function() {
      bindFormEvents(pageName);
    }, 100);
  }

  function bindFormEvents(pageName) {
    switch(pageName) {
      case '网点收寄':
        bindSendForm();
        break;
      case '寄递运输':
        bindTranForm();
        break;
      case '投递处理':
        bindSubForm();
        break;
      case '查询物流':
        bindTrackingForm();
        break;
      case '设置':
        bindSettingsForm();
        break;
    }
  }

  // ---------- 同时支持单条和多条记录的版本 ----------
function setupPhoneSuggest(inputId, nameFieldId, addressFieldId, postcodeFieldId) {
    var $input = $('#' + inputId);
    if (!$input.length) return;

    $input.off('input.phoneSuggest').on('input.phoneSuggest', function() {
        var $this = $(this);
        var phone = $.trim($this.val());
        if (phone === '') return;

        var timer = setTimeout(function() {
            $.ajax({
                url: 'getaddress.php',
                type: 'GET',
                data: { phone: phone },
                dataType: 'json',
                success: function(res) {
                    if (!res.success) return;
                    
                    // 移除已有的建议框
                    $('.phone-suggest-container[data-for="' + inputId + '"]').remove();

                    var $container = $('<div class="phone-suggest-container" data-for="' + inputId + '"></div>');
                    var offset = $input.offset();
                    $container.css({
                        top: offset.top + $input.outerHeight(),
                        left: offset.left,
                        width: $input.outerWidth()
                    });

                    // 处理返回的数据（支持数组或单个对象）
                    var items = [];
                    if (res.data && Array.isArray(res.data)) {
                        // 如果 data 是数组
                        items = res.data;
                    } else if (res.data) {
                        // 如果 data 是单个对象
                        items = [res.data];
                    }

                    if (items.length === 0) {
                        $container.remove();
                        return;
                    }

                    // 填充数据项
                    $.each(items, function(idx, item) {
                        var $item = $('<div class="phone-suggest-item"></div>');
                        $item.html('<strong>' + escapeHtml(item.name) + '</strong> <small>' + escapeHtml(item.phone) + '</small><br><span>' + escapeHtml(item.address) + ' ' + escapeHtml(item.postcode) + '</span>');
                        $item.data('item', item);
                        $item.on('click', function(e) {
                            e.stopPropagation();
                            var selected = $(this).data('item');
                            $('#' + nameFieldId).val(selected.name);
                            $('#' + addressFieldId).val(selected.address);
                            $('#' + postcodeFieldId).val(selected.postcode);
                            $container.remove();
                        });
                        $container.append($item);
                    });

                    $('body').append($container);
                },
                error: function() {
                    // 出错时不显示任何提示
                }
            });
        }, 300);

        $this.data('suggestTimer', timer);
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            if (m === '"') return '&quot;';
            return m;
        });
    }
}  

  // 绑定网点收寄表单
  function bindSendForm() {
    layui.use(['form', 'layer'], function(){
      var form = layui.form;
      var layer = layui.layer;
      
      form.on('submit(sendSubmit)', function(data){
        $.ajax({
          url: 'send.php',
          type: 'POST',
          data: data.field,
          dataType: 'json',
          beforeSend: function() {
            layer.load(2);
          },
          success: function(response) {
            layer.closeAll('loading');
            if(response.success) {
              layer.msg('提交成功！', {icon: 1});
            } else {
              layer.msg(response.message || '提交失败！', {icon: 2});
            }
          },
          error: function() {
            layer.closeAll('loading');
            layer.msg('请求失败，请稍后重试', {icon: 2});
          }
        });
        return false;
      });

      // 新增：为寄件人手机号和收件人手机号添加自动建议功能
      setupPhoneSuggest('senderphone_input', 'sender_name', 'sender_address', 'sender_postcode');
      setupPhoneSuggest('tophone_input', 'to_name', 'to_address', 'to_postcode');
    });
  }

  // 绑定寄递运输表单
  function bindTranForm() {
    layui.use(['form', 'layer'], function(){
      var form = layui.form;
      var layer = layui.layer;
      
      form.on('submit(tranSubmit)', function(data){
        $.ajax({
          url: 'tran.php',
          type: 'POST',
          data: data.field,
          dataType: 'json',
          beforeSend: function() {
            layer.load(2);
          },
          success: function(response) {
            layer.closeAll('loading');
            if(response.success) {
              layer.msg('提交成功！', {icon: 1});
            } else {
              layer.msg(response.message || '提交失败！', {icon: 2});
            }
          },
          error: function() {
            layer.closeAll('loading');
            layer.msg('请求失败，请稍后重试', {icon: 2});
          }
        });
        return false;
      });
    });
  }

  // 绑定投递处理表单
  function bindSubForm() {
    layui.use(['form', 'layer'], function(){
      var form = layui.form;
      var layer = layui.layer;
      
      form.on('submit(subSubmit)', function(data){
        $.ajax({
          url: 'sub.php',
          type: 'POST',
          data: data.field,
          dataType: 'json',
          beforeSend: function() {
            layer.load(2);
          },
          success: function(response) {
            layer.closeAll('loading');
            if(response.success) {
              layer.msg('提交成功！', {icon: 1});
            } else {
              layer.msg(response.message || '提交失败！', {icon: 2});
            }
          },
          error: function() {
            layer.closeAll('loading');
            layer.msg('请求失败，请稍后重试', {icon: 2});
          }
        });
        return false;
      });
    });
  }

  // 绑定物流查询表单
  function bindTrackingForm() {
    $('#trackingForm').on('submit', function(e){
      e.preventDefault();
      tracking();
      return false;
    });
  }
  
  // 绑定更新设置表单
  function bindSettingsForm() {
    $('#settingsForm').on('submit', function(e){
      e.preventDefault();
      updateSettings();
      return false;
    });
  }

  function tracking() {
    var mail = $('#trackmail').val();
    if (!mail) {
      layui.use('layer', function(){
        var layer = layui.layer;
        layer.msg('请输入邮件号码', {icon: 2});
      });
      return;
    }
    
    $.ajax({
      url: 'tracking.php',
      type: 'POST',
      data: {mail: mail},
      dataType: 'json',
      beforeSend: function() {
        layui.use('layer', function(){
          var layer = layui.layer;
          layer.load(2);
        });
      },
      success: function(response) {
          layui.use('layer', function(){
            var layer = layui.layer;
            layer.closeAll('loading');
            
            var mailinfo = Object.values(JSON.parse(response['mailinfo']).mailinfo);
            
            var html = '<div class="tracking-result">';
            html += '<h5>邮件追踪信息 (单号: ' + mail + ')</h5>';
            
            if (mailinfo && mailinfo.length > 0) {
              mailinfo.forEach(function(item) {
                html += '<div class="tracking-item">';
                html += '<div class="tracking-time">' + item.time + '</div>';
                html += '<div class="tracking-desc">' + item.description + '</div>';
                html += '</div>';
              });
            } else {
              html += '<div class="tracking-item">暂无追踪信息</div>';
            }
            
            html += '</div>';
            $('#tracking').html(html);
          });
        },
      error: function() {
        layui.use('layer', function(){
          var layer = layui.layer;
          layer.closeAll('loading');
          layer.msg('查询失败，请稍后重试', {icon: 2});
        });
      }
    });
  }
  
  function updateSettings() {
    var pwd = $('#password').val();
    var newpwd = $('#new_password').val();
    var input = document.getElementById('headimg');
    
    
    
    layui.use('layer', function(){
        var layer = layui.layer;
        const files = input.files;
    
        // 检查是否有选择文件
        if (!files || files.length === 0) {
            
            if (!pwd) {
                layui.use('layer', function(){
                    var layer = layui.layer;
                    layer.msg('请输入旧密码', {icon: 2});
                });
                return;
            }
            
            if (!newpwd) {
                layui.use('layer', function(){
                    var layer = layui.layer;
                    layer.msg('请输入新密码', {icon: 2});
                });
                return;
            }
            
            // 没有选择头像文件，直接更新密码
            $.ajax({
                url: 'setuser.php',
                type: 'POST',
                data: {
                    password: pwd,
                    new_password: newpwd,
                    headimg: '' // 或者不传这个字段
                },
                dataType: 'json',
                beforeSend: function() {
                    layer.load(2);
                },
                success: function(response) {
                    layer.closeAll('loading');
                    
                    if (response.success) {
                        layer.msg('密码更新成功，请重新登录', {icon: 1});
                        
                        setTimeout(function() {
                            $.ajax({
                                url: 'logout.php',
                                type: 'POST',
                                dataType: 'json',
                                success: function(logoutResponse) {
                                    if (logoutResponse.success) {
                                        window.location.href = window.location.pathname + '?refresh=true&t=' + new Date().getTime();
                                    } else {
                                        layer.msg('自动退出失败，请手动退出', {icon: 2});
                                    }
                                },
                                error: function() {
                                    layer.msg('退出请求失败', {icon: 2});
                                }
                            });
                        }, 1500);
                    } else {
                        layer.msg(response.message || '密码更新失败', {icon: 2});
                    }
                },
                error: function() {
                    layer.closeAll('loading');
                    layer.msg('请求失败，请稍后重试', {icon: 2});
                }
            });
            return;
        }
        
        // 有选择文件，先读取文件
        const file = files[0];
        
        // 验证文件类型
        if (!file.type.startsWith('image/')) {
            layer.msg('请选择图片文件', {icon: 2});
            return;
        }
        
        // 验证文件大小（例如限制为 2MB）
        if (file.size > 2 * 1024 * 1024) {
            layer.msg('图片大小不能超过 2MB', {icon: 2});
            return;
        }
        
        // 创建 FileReader 对象
        const reader = new FileReader();
        
        // 读取完成后的回调函数
        reader.onload = function(e) {
            const headimg = e.target.result;
            
            // 在文件读取完成后才发送 AJAX 请求
            $.ajax({
                url: 'setuser.php',
                type: 'POST',
                data: {
                    password: '',
                    new_password: '',
                    headimg: headimg
                },
                dataType: 'json',
                beforeSend: function() {
                    layer.load(2);
                },
                success: function(response) {
                    layer.closeAll('loading');
                    
                    if (response.success) {
                        layer.msg('头像更新成功，正在刷新页面......', {icon: 1});
                        setTimeout(function() {
                            window.location.href = window.location.pathname + '?refresh=true&t=' + new Date().getTime();
                          }, 1000);
                    } else {
                        layer.msg(response.message || '密码更新失败', {icon: 2});
                    }
                },
                error: function() {
                    layer.closeAll('loading');
                    layer.msg('请求失败，请稍后重试', {icon: 2});
                }
            });
        };
        
        reader.onerror = function(e) {
            layer.closeAll('loading');
            layer.msg('文件读取失败，请重试', {icon: 2});
        };
        
        // 开始读取文件
        reader.readAsDataURL(file);
    });
}

  function updatePageContent(pageName, htmlContent) {
    pageContents[pageName] = htmlContent;
    if (currentPage === pageName) {
      loadPageContent(pageName);
    }
  }

  function showLogin() {
    layui.use('layer', function(){
      var layer = layui.layer;
      layer.open({
        type: 1,
        title: '用户登录',
        area: ['400px', '300px'],
        content: $('#loginModal').html(),
        success: function(layero, index){
          layui.form.render();
        }
      });
    });
  }

  function quitLogin() {
    layui.use(['layer', 'form'], function(){
      var layer = layui.layer;
      var form = layui.form;
      
      layer.confirm('确定要退出登录吗？', {icon: 3, title:'提示'}, function(index){
        setTimeout(function() {
          $.ajax({
            url: 'logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(logoutResponse) {
              if (logoutResponse.success) {
                window.location.href = window.location.pathname + '?refresh=true&t=' + new Date().getTime();
              } else {
                layer.msg('请求失败', {icon: 2});
              }
            },
            error: function() {
              layer.msg('请求失败', {icon: 2});
            }
          });
        }, 1500);
        layer.close(index);
      });
    });
  }

  function updateUserStatus(username, isLoggedIn = false) {
    if (isLoggedIn) {
      layer.msg('登录成功，正在刷新页面...', {icon: 1});
      setTimeout(function() {
        window.location.href = window.location.pathname + '?refresh=true&t=' + new Date().getTime();
      }, 1000);
    }
  }
  
  function drawArcText(ctx, text, centerX, centerY, radius, startAngle, endAngle, fontSize, color, isInner = false) {
    if (!text || text.trim() === '') return;
    
    const chars = text.split('');
    const angleRange = endAngle - startAngle;
    
    ctx.font = `bold ${fontSize}px 'LFangSong'`;
    ctx.fillStyle = color;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    
    const charWidths = chars.map(char => ctx.measureText(char).width);
    const totalWidth = charWidths.reduce((sum, width) => sum + width, 0);
    
    const circumference = radius * angleRange;
    
    let spacing = 0;
    if (chars.length > 1) {
        if (totalWidth <= circumference) {
            spacing = (circumference - totalWidth) / (chars.length - 1);
        } else {
            const maxOverlap = fontSize * 0.15;
            const neededSpacing = (circumference - totalWidth) / (chars.length - 1);
            spacing = Math.max(neededSpacing, -maxOverlap);
        }
    }
    
    const arcLength = totalWidth + (chars.length - 1) * spacing;
    const angleOffset = (angleRange - arcLength / radius) / 2;
    let currentAngle = startAngle + angleOffset;
    
    for (let i = 0; i < chars.length; i++) {
        const char = chars[i];
        const charWidth = charWidths[i];
        
        const charAngle = currentAngle + charWidth / (2 * radius);
        
        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate(charAngle);
        ctx.scale(-1, -1);
        
        if (isInner) {
            ctx.rotate(Math.PI);
            ctx.fillText(char, 0, radius);
        } else {
            ctx.fillText(char, 0, -radius);
        }
        
        ctx.restore();
        
        currentAngle += (charWidth + spacing) / radius;
    }
  }

  function getCurrentDate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hour = String(now.getHours()).padStart(2, '0');
    
    return `${year}.${month}.${day}.${hour}`;
  }

  function generatePostmark(locationId, postOfficeId, canvasId) {
    const location = document.getElementById(locationId).value;
    const postOffice = [...document.getElementById(postOfficeId).value].reverse().join('');
    const dateStr = getCurrentDate();
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext("2d");
    
    const displayScale = 4;
    const actualDiameterMM = 25;
    const dpi = 96;
    const actualDiameterPx = (actualDiameterMM / 25.4) * dpi;
    
    canvas.width = actualDiameterPx * displayScale;
    canvas.height = actualDiameterPx * displayScale;
    
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = (actualDiameterPx / 2) * displayScale;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    ctx.font = `bold ${9 * displayScale}px 'Helvetica', monospace`;
    ctx.fillStyle = "#000000";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(dateStr, centerX, centerY);
    
    ctx.save();
    ctx.translate(centerX, centerY);
    ctx.rotate(Math.PI / 2);
    ctx.translate(-centerX, -centerY);
    
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius - 4, 0, 2 * Math.PI);
    ctx.lineWidth = 5;
    ctx.strokeStyle = "#000000";
    ctx.stroke();
    
    const arcFontSize = 12 * displayScale;
    
    drawArcText(ctx, location, centerX-30, centerY, radius * 0.65, 
                Math.PI * 0.25, Math.PI * 0.75, arcFontSize, "#000000", false);
    
    drawArcText(ctx, postOffice, centerX+30, centerY, radius * 0.65, 
                Math.PI * 1.25, Math.PI * 1.75, arcFontSize, "#000000", true);
    
    ctx.restore();
  }

  function showMail(value) {
      var layer = layui.layer;
      var content;
      $.ajax({
      url: 'trackinfo.php',
      type: 'POST',
      data: {mail: value},
      dataType: 'json',
      beforeSend: function() {
        layui.use('layer', function(){
          var layer = layui.layer;
          layer.load(2);
        });
      },
      success: function(response) {
          layui.use('layer', function(){
            var layer = layui.layer;
            layer.closeAll('loading');
            
            var mailinfo = Object.values(JSON.parse(response['mailinfo']).mailinfo);
            
            var html = '<div class="tracking-result">';
            html += '<h5>邮件详情 ( ' + value + ')</h5>';
            
            if (mailinfo && mailinfo.length > 0) {
              mailinfo.forEach(function(item) {
                html += '<div class="tracking-item">';
                html += '<div class="tracking-time">' + item.time + '</div>';
                html += '<div class="tracking-desc">' + item.description + '</div>';
                html += '</div>';
              });
            } else {
              html += '<div class="tracking-item">暂无详情信息</div>';
            }
            
            html += '</div>';
            $('#trackinfo').html(html);
          });
        },
      error: function() {
        layui.use('layer', function(){
          var layer = layui.layer;
          layer.closeAll('loading');
          layer.msg('查询失败，请稍后重试', {icon: 2});
        });
      }
    });
  }
  
  function settingMembers(user) {
      var QPTchecked = "";
      var OSchecked = "";
         $.ajax({
            type: "POST", // 请求类型，可以是 "GET" 或 "POST"
            url: "./getperms.php", // 请求的URL
            data: { username: user }, // 发送到服务器的数据
            success: function(data) { // 请求成功后的回调函数
                QPTchecked = data['canQPT'];
                OSchecked = data['canOS'];
                layer.open({
                    title: `修改${user}的权限`,
                    content: `<p>快速打单&nbsp;&nbsp;&nbsp;<input id="quickPrintTicket" class="ios-switch" type="checkbox" ${QPTchecked}></p><br /><p>打开设置&nbsp;&nbsp;&nbsp;<input id="openSettings" class="ios-switch" type="checkbox"  ${OSchecked}></p>`,
                    success: function(layero, index) {
                        document.getElementById("quickPrintTicket").addEventListener("click", function() { 
                            $.ajax({
                               type: "POST", // 请求类型，可以是 "GET" 或 "POST"
                               url: "./setperms.php", // 请求的URL
                               data: { username: user, QPT: document.getElementById("quickPrintTicket").checked ? 1 : 0, OS: document.getElementById("openSettings").checked ? 1 : 0 }, // 发送到服务器的数据
                               error: function(xhr, status, error) { // 请求失败后的回调函数
                                   layer.open({content: "更新失败！", icon: 2});
                               }
                            });
                        });
                        
                        document.getElementById("openSettings").addEventListener("click", function() { 
                            $.ajax({
                               type: "POST", // 请求类型，可以是 "GET" 或 "POST"
                               url: "./setperms.php", // 请求的URL
                               data: { username: user, QPT: document.getElementById("quickPrintTicket").checked ? 1 : 0, OS: document.getElementById("openSettings").checked ? 1 : 0 }, // 发送到服务器的数据
                               error: function(xhr, status, error) { // 请求失败后的回调函数
                                   layer.open({content: "更新失败！", icon: 2});
                               }
                            });
                        });
                    }
                 });
            },
            error: function(xhr, status, error) { // 请求失败后的回调函数
                layer.open({content: "获取权限信息失败！", icon: 2});
            }
        });
     
  }

  $(document).ready(function() {
    <?php if($refresh_page): ?>
    layui.use('layer', function(){
      var layer = layui.layer;
      setTimeout(function() {
        layer.msg('页面已刷新', {icon: 1, time: 1500});
      }, 300);
    });
    <?php endif; ?>

    // 初始化页面内容（重点：为网点收寄字段添加了 id）
    updatePageContent('网点收寄', `
      <div>
        <h4>收寄信息录入</h4>
        <form class="layui-form" lay-filter="sendForm">
          <div class="layui-form-item">
            <label class="layui-form-label required">收寄局</label>
            <div class="layui-input-inline">
              <input type="text" name="senderpost" value="<?php echo $userpost ?>" class="layui-input" readonly>
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">寄达局</label>
            <div class="layui-input-inline">
              <input type="text" name="topost" class="layui-input" lay-verify="required">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">寄件人</label>
            <div class="layui-input-inline">
              <input type="text" name="sender" id="sender_name" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">寄件手机号</label>
            <div class="layui-input-inline">
              <input type="text" name="senderphone" id="senderphone_input" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">寄件人邮编</label>
            <div class="layui-input-inline">
              <input type="text" name="senderpc" id="sender_postcode" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">寄件人地址</label>
            <div class="layui-input-inline" style="width: 300px;">
              <input type="text" name="senderaddress" id="sender_address" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">收件人</label>
            <div class="layui-input-inline">
              <input type="text" name="to" id="to_name" class="layui-input" lay-verify="required">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">收件手机号</label>
            <div class="layui-input-inline">
              <input type="text" name="tophone" id="tophone_input" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">收件人邮编</label>
            <div class="layui-input-inline">
              <input type="text" name="topc" id="to_postcode" class="layui-input"  lay-verify="required">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">收件人地址</label>
            <div class="layui-input-inline" style="width: 300px;">
              <input type="text" name="toaddress" id="to_address" class="layui-input" lay-verify="required">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">重量</label>
            <div class="layui-input-inline">
              <input type="text" name="g" class="layui-input" lay-verify="required">
            </div>
            <label class="layui-form-label">g</label>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">业务类型</label>
            <div class="layui-input-inline">
              <select name="type" lay-verify="required">
                <option value="国内平常信函">111 国内平常信函</option>
                <option value="国内给据信函">112 国内给据信函</option>
                <option value="国内平常明信片">121 国内平常明信片</option>
                <option value="国内给据明信片">122 国内给据明信片</option>
                <option value="国内平常印刷品">131 国内平常印刷品</option>
                <option value="国内给据印刷品">132 国内给据印刷品</option>
                <option value="国内平常盲人读物">141 国内平常盲人读物</option>
                <option value="国内给据盲人读物">142 国内给据盲人读物</option>
                <option value="国内义务兵免费信件">151 国内义务兵免费信件</option>
                <option value="国内平常商业信函">211 国内平常商业信函</option>
                <option value="国内给据商业信函">212 国内给据商业信函</option>
              </select>
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">标准资费</label>
            <div class="layui-input-inline">
              <input type="text" name="b" class="layui-input" lay-verify="required">
            </div>
            <label class="layui-form-labe">元</label>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">人员</label>
            <div class="layui-input-inline">
              <input type="text" name="member" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">邮件号码</label>
            <div class="layui-input-inline">
              <input type="text" id="mail" name="mail" class="layui-input" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <i class="fas fa-camera" style="font-size: 30px; color: #1E9FFF; cursor: pointer; display: inline-block; transition: all 0.2s ease; padding: 8px; border-radius: 50%;" onmouseover="this.style.backgroundColor='rgba(30,159,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'" onclick="startRealScan('mail')"></i>
          <div class="layui-form-item">
            <button class="layui-btn" lay-submit lay-filter="sendSubmit">提交</button>
          </div>
        </form>
      </div>
    `);

    updatePageContent('寄递运输', `
      <div>
        <h4>运输信息录入</h4>
        <form class="layui-form" lay-filter="tranForm">
          <div class="layui-form-item">
            <label class="layui-form-label required">处理机构</label>
            <div class="layui-input-inline">
              <input type="text" name="centersenderpost" value="<?php echo $userpost ?>" class="layui-input" readonly>
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">总包寄达局</label>
            <div class="layui-input-inline">
              <input type="text" name="centertopost" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">总包号码</label>
            <div class="layui-input-inline">
              <input type="text" name="centernum" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">总包条码</label>
            <div class="layui-input-inline" style="width: 300px;">
              <input type="text" name="centercode" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">邮路</label>
            <div class="layui-input-inline" style="width: 300px;">
              <input type="text" name="postpath" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">操作</label>
            <div class="layui-input-inline">
              <select name="action" lay-verify="required">
                <option value="揽投封发">揽投封发</option>
                <option value="揽投发运/封车">揽投发运/封车</option>
                <option value="扫描封发">扫描封发</option>
                <option value="处理中心解车" >处理中心解车</option>
                <option value="邮件到达处理中心" >邮件到达处理中心</option>
                <option value="处理中心扫描配发">处理中心扫描配发</option>
                <option value="邮件离开处理中心">邮件离开处理中心</option>
                <option value="处理中心封车">处理中心封车</option>
              </select>
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">人员</label>
            <div class="layui-input-inline">
              <input type="text" name="member" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">邮件号码</label>
            <div class="layui-input-inline">
              <input type="text" id="mail" name="mail" class="layui-input" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <i class="fas fa-camera" style="font-size: 30px; color: #1E9FFF; cursor: pointer; display: inline-block; transition: all 0.2s ease; padding: 8px; border-radius: 50%;" onmouseover="this.style.backgroundColor='rgba(30,159,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'" onclick="startRealScan('mail')"></i>
          <div class="layui-form-item">
            <button class="layui-btn" type="button" onclick="showMail(document.getElementById('mail').value)">查看邮件详情</button>
          </div>
          <div class="layui-form-item">
            <button class="layui-btn" lay-submit lay-filter="tranSubmit">提交</button>
          </div>
          <div id="trackinfo"></div>
        </form>
      </div>
    `);
    
    updatePageContent('投递处理', `
      <div>
        <h4>投递信息录入</h4>
        <form class="layui-form" lay-filter="subForm">
          <div class="layui-form-item">
            <label class="layui-form-label required">处理机构</label>
            <div class="layui-input-inline">
              <input type="text" name="centersenderpost" value="<?php echo $userpost ?>" class="layui-input" readonly>
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">操作</label>
            <div class="layui-input-inline">
              <select name="action" lay-verify="required">
                <option value="白给多件">白给多件</option>
                <option value="到达投递机构">到达投递机构</option>
                <option value="投递邮件接收-下段">投递邮件接收-下段</option>
                <option value="投递结果反馈-妥投">投递结果反馈-妥投</option>
                <option value="投递回班交接">投递回班交接</option>
              </select>
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">详情</label>
            <div class="layui-input-inline">
              <input type="text" name="info" class="layui-input" style="width: 500px;">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label">人员</label>
            <div class="layui-input-inline">
              <input type="text" name="member" class="layui-input">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">邮件号码</label>
            <div class="layui-input-inline">
              <input type="text" id="mail" name="mail" class="layui-input" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <i class="fas fa-camera" style="font-size: 30px; color: #1E9FFF; cursor: pointer; display: inline-block; transition: all 0.2s ease; padding: 8px; border-radius: 50%;" onmouseover="this.style.backgroundColor='rgba(30,159,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'" onclick="startRealScan('mail')"></i>
          <div class="layui-form-item">
            <button class="layui-btn" type="button" onclick="showMail(document.getElementById('mail').value)">查看邮件详情</button>
          </div>
          <div class="layui-form-item">
            <button class="layui-btn" lay-submit lay-filter="subSubmit">提交</button>
          </div>
          <div id="trackinfo"></div>
        </form>
      </div>
    `);
    
    updatePageContent('查询物流', `
      <div>
        <h4>内网物流查询</h4>
        <form class="layui-form" id="trackingForm">
          <div class="layui-form-item">
            <label class="layui-form-label required">邮件号码</label>
            <div class="layui-input-inline">
              <input type="text" id="mail" name="mail" class="layui-input" id="trackmail" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <i class="fas fa-camera" style="font-size: 30px; color: #1E9FFF; cursor: pointer; display: inline-block; transition: all 0.2s ease; padding: 8px; border-radius: 50%;" onmouseover="this.style.backgroundColor='rgba(30,159,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'" onclick="startRealScan('mail')"></i>
          <button class="layui-btn" type="submit">查询</button>
        </form>
        <div id="tracking"></div>
      </div>
    `);
    
    updatePageContent('生成条码', `
      <div>
        <h4>寄递条码生成</h4>
        <form class="layui-form">
          <div class="layui-form-item">
            <label class="layui-form-label required">邮件号码</label>
            <div class="layui-input-inline">
              <input type="text" name="mail" class="layui-input" id="tm" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <button class="layui-btn" type="button" onclick='JsBarcode("#barcode", document.getElementById("tm").value, { format: "CODE128", lineColor: "#000", width: 2, height: 100, displayValue: true })'>生成</button>
        </form>
        <svg id="barcode"></svg>
      </div>
    `);
    
    updatePageContent('电子日戳', `
      <div>
        <h4>网点电子日戳</h4>
        <form class="layui-form">
          <div class="layui-form-item">
            <label class="layui-form-label required">上环</label>
            <div class="layui-input-inline">
              <input type="text" name="up" class="layui-input" id="up" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <div class="layui-form-item">
            <label class="layui-form-label required">下环</label>
            <div class="layui-input-inline">
              <input type="text" name="down" class="layui-input" id="down" style="width: 300px;" lay-verify="required">
            </div>
          </div>
          <button class="layui-btn" type="button" onclick='generatePostmark("up", "down", "rc")'>生成</button>
        </form>
        <canvas id="rc"></canvas>
      </div>
    `);
    
    updatePageContent('设置', `
      <div>
        <h4>设置</h4>
        <?php echo $enableOS ? '<form class="layui-form" lay-filter="settingsForm" id="settingsForm"> <div class="layui-form-item"> <label class="layui-form-label">账号</label> <div class="layui-input-inline"> <input class="layui-input" type="text" value="'.$username.'" readonly> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">密码</label> <div class="layui-input-inline"> <input class="layui-input" type="password" name="password" id="password"> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">设置新密码</label> <div class="layui-input-inline"> <input class="layui-input" type="password" name="new_password" id="new_password"> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">注册日期</label> <div class="layui-input-inline"> <input class="layui-input" type="text" value="'.$userct.'" readonly> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">更新日期</label> <div class="layui-input-inline"> <input class="layui-input" type="text" value="'.$userut.'" readonly> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">权限</label> <div class="layui-input-inline"> <input class="layui-input" type="text" value="'.$userrole.'" readonly> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">邮政单位</label> <div class="layui-input-inline"> <input class="layui-input" type="text" value="'.$userpost.'" readonly> </div> </div> <div class="layui-form-item"> <label class="layui-form-label">上传头像</label> <div class="layui-input-inline"> <input class="layui-input" type="file" name="headimg" id="headimg" accept="image/*" /> </div> </div> <button class="layui-btn" lay-submit lay-filter="settingsSubmit">更新</button> </form>' : "<p>邮政机构管理员关闭了此功能！</p>" ?>
      </div>
    `);
    
    updatePageContent('邮政机构管理', `
      <div>
        <h4>管理下属邮政机构（当前机构：<?php echo htmlspecialchars($userpost); ?>）</h4>
        <table class="layui-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>邮政机构</th>
                <th>权限</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['id']); ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['post']); ?></td>
                <td><button class="layui-btn" onclick="settingMembers('<?php echo htmlspecialchars($u['username']); ?>')">设置</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
      </div>
    `);
    
    updatePageContent('邮资机戳', `
      <div>
        <h4>生成邮资机戳</h4>
        <br />
        <form class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label required">地名（仅限4字）</label>
                <div class="layui-input-inline">
                    <input type="text" name="region" class="layui-input" id="region" style="width: 300px;" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label required">邮局名（仅限2字）</label>
                <div class="layui-input-inline">
                    <input type="text" name="postname" class="layui-input" id="postname" style="width: 300px;" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label required">台席（仅限2位数字）</label>
                <div class="layui-input-inline">
                    <input type="text" name="tx" class="layui-input" id="tx" style="width: 300px;" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label required">邮资（元）</label>
                <div class="layui-input-inline">
                    <input type="number" name="price" class="layui-input" id="price" style="width: 300px;" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label required">右下角编号（最多7位）</label>
                <div class="layui-input-inline">
                    <input type="text" name="number" class="layui-input" id="number" style="width: 300px;" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item">
                <label for="file" class="layui-form-label">请上传图片</label>
                <input type="file" id="uploadYZJC_Image" accept="image/*" />
                <br />
                <br />
                <button type="button" onclick="yzjc()" class="layui-btn">即刻生成</button>
            </div>
            
            <canvas id="yzjc" height="283.464" width="793.701" />
        </form>
        
      </div>
      <script src="./js/qrcode.min.js"><\/script>
      <script>
            function yzjc() {
                var c = document.getElementById("yzjc");
                var ctx = c.getContext("2d");
                
                var w = c.width;
                var h = c.height;
                c.width = w;
                c.height = h;
                
                var region = document.getElementById("region").value;
                var postname = document.getElementById("postname").value;
                var tx = document.getElementById("tx").value;
                var price = (Math.min(Math.floor(parseFloat(document.getElementById("price").value) || 0), 999) + (parseFloat(document.getElementById("price").value) % 1).toFixed(2).substring(1)).padStart(6, '0').replace('.',' ');
                var number = document.getElementById("number").value.padStart(7, '0');
                
                var img = new Image();
                img.src = "./img/yzjc.png";
                
                img.onload = function() {
                    ctx.drawImage(img, 0, 0, 264.567*3, 94.488*3);
                };
                
                var UZ = new Image();
                UZ.src = "./img/postlogo.png";
                UZ.onload = function() {
                    ctx.drawImage(UZ, 590, 20, 886/4.2, 224/4.2);
                }
                
                const input = document.getElementById('uploadYZJC_Image');
                const files = input.files;
                
                // 获取第一个文件
                const file = files[0];
                
                // 创建 FileReader 对象
                const reader = new FileReader();
                
                // 读取完成后的回调函数
                reader.onload = function(e) {
                    var YZJC_Image = new Image();
                    YZJC_Image.src = e.target.result;
                    YZJC_Image.onload = function() {
                        ctx.clearRect(71.764*3, 3.268*3, 119.055*3, 88.063*3);
                        ctx.drawImage(YZJC_Image, 71.764*3, 3.268*3, 119.055*3, 88.063*3);
                    };
                };
                
                reader.readAsDataURL(file);
                    
                
                // 设置字体
                ctx.font = '39px "LFangSong"';
                
                // 填充文字
                ctx.fillStyle = '#E27891';
                ctx.fillText(region, 40, 55);
                ctx.fillText(postname, 50, 100);
                
                // 设置字体
                ctx.font = '39px "ArialNarrow"';
                
                // 填充文字
                ctx.fillText(tx, 110, 95);
                
                // 生成二维码的 Base64 URL
                QRCode.toDataURL('https://xyd.laide.net.cn/', {
                    width: 53.291 * 3,
                    margin: 0,
                    color: {
                        dark: '#E27891',
                        light: '#FFFFFF'
                    },
                    errorCorrectionLevel: 'L'
                }, function(err, url) {
                    if (err) {
                        console.error(err);
                        return;
                    }
                    
                    // 创建图片对象
                    var img = new Image();
                    img.onload = function() {
                        // 把二维码画到指定位置，完全不影响 Canvas 其他内容
                        ctx.drawImage(img, 15, 110, 53.291 * 3, 53.291 * 3);
                    };
                    img.src = url;
                });
                
                ctx.font = '60px "ArialNarrow"';
                ctx.fillText(price, 635, 155);
                
                ctx.font = '34px "ArialNarrow"';
                ctx.fillText('<?php echo date("Y m d H") ?>', 602, 212);
                
                ctx.fillText(number, 652, 262);
            }
      <\/script>
    `);

    layui.use(['element', 'layer', 'util', 'form'], function(){
      var element = layui.element
      , layer = layui.layer
      , util = layui.util
      , form = layui.form
      , $ = layui.$;
      
      util.event('lay-header-event', {
        menuRight: function(){
          layer.open({
            type: 1,
            content: '<div style="padding: 15px;">新一代营业渠道系统 v2.1 <br />1. 将邮资机戳logo改为了“模拟邮政系统”</div>',
            area: ['260px', '100%'],
            offset: 'rt',
            anim: 5,
            shadeClose: true
          });
        }
      });
      
      form.on('submit(loginSubmit)', function(data){
        $.ajax({
          url: 'login.php',
          type: 'POST',
          data: data.field,
          dataType: 'json',
          success: function(response) {
            if(response.success) {
              layer.msg('登录成功，正在刷新页面...', {icon: 1});
              setTimeout(function() {
                window.location.href = window.location.pathname + '?refresh=true&t=' + new Date().getTime();
              }, 1000);
            } else {
              layer.msg(response.message || '登录失败！', {icon: 2});
            }
          },
          error: function() {
            layer.msg('请求失败，请稍后重试', {icon: 2});
          }
        });
        return false;
      });
    });

    // 全局点击隐藏建议框
    $(document).on('click', function(e) {
      $('.phone-suggest-container').each(function() {
        var $container = $(this);
        var $input = $('#' + $container.data('for'));
        if (!$container.is(e.target) && $container.has(e.target).length === 0 && !$input.is(e.target) && $input.has(e.target).length === 0) {
          $container.remove();
        }
      });
    });
  });
  </script>
</head>
<body>
<div class="layui-layout layui-layout-admin">
  <div class="layui-header">
    <div class="layui-logo layui-hide-xs layui-bg-black">新一代营业渠道系统</div>
    <ul class="layui-nav layui-layout-right">
      <li class="layui-nav-item layui-hide layui-show-md-inline-block">
        <a href="javascript:;">
          <img src="<?php echo $userimg ?>" class="layui-nav-img">
          <span id="usernameDisplay"><?php echo $username ?></span>
        </a>
        <dl class="layui-nav-child" id="userMenu">
          <?php if($userlogin): ?>
            <dd><a href="javascript:quitLogin();">退出登录</a></dd>
            <dd><a href="javascript:void(0);" onclick="checkLogin('设置')">设置</a></dd>
          <?php else: ?>
            <dd><a href="javascript:showLogin();">登录</a></dd>
          <?php endif; ?>
        </dl>
      </li>
      <li class="layui-nav-item" lay-header-event="menuRight" lay-unselect>
        <a href="javascript:;">
          <i class="layui-icon layui-icon-more-vertical"></i>
        </a>
      </li>
    </ul>
  </div>
  
  <div class="layui-side layui-bg-black">
    <div class="layui-side-scroll">
      <ul class="layui-nav layui-nav-tree" lay-filter="test">
          <?php if($postadmin) echo "<li class=\"layui-nav-item\"> <a href=\"javascript:void(0);\" onclick=\"checkLogin('邮政机构管理')\">邮政机构管理</a> </li>"; else echo "<li class=\"layui-nav-item\"> <a href=\"javascript:;\">寄递业务</a> <dl class=\"layui-nav-child\"> <dd><a href=\"javascript:void(0);\" onclick=\"checkLogin('网点收寄')\">网点收寄</a></dd> <dd><a href=\"javascript:void(0);\" onclick=\"checkLogin('寄递运输')\">寄递运输</a></dd> </dl> </li> <li class=\"layui-nav-item\"> <a href=\"javascript:;\">揽投管理</a> <dl class=\"layui-nav-child\"> <dd><a href=\"javascript:void(0);\" onclick=\"checkLogin('投递处理')\">投递处理</a></dd> </dl> </li> <li class=\"layui-nav-item\"> <a href=\"javascript:;\">便捷操作</a> <dl class=\"layui-nav-child\"> <dd><a href=\"javascript:void(0);\" onclick=\"checkLogin('生成条码')\">生成条码</a></dd> <dd><a href=\"javascript:void(0);\" onclick=\"checkLogin('电子日戳')\">电子日戳</a></dd> <dd> <a href=\"javascript:void(0);\" onclick=\"checkLogin('邮资机戳')\">邮资机戳</a></dd> </dl> </li> <li class=\"layui-nav-item\"> <a href=\"javascript:void(0);\" onclick=\"checkLogin('查询物流')\">查询物流</a> </li>"; ?>
          <?php if($userrole=="admin") echo "<li class=\"layui-nav-item\"> <a href=\"./dashboard\">仪表盘</a> </li>" ?>
      </ul>
    </div>
  </div>
  
  <div class="layui-body">
    <div style="padding: 15px;" id="mainContent"></div>
  </div>
  
  <div class="layui-footer">
    &copy; 2026 Laide
  </div>
</div>

<!-- 登录弹窗 -->
<div id="loginModal" style="display: none; padding: 20px;">
  <form class="layui-form" id="loginForm">
    <div class="layui-form-item">
      <label class="layui-form-label">用户名</label>
      <div class="layui-input-block">
        <input type="text" name="username" required lay-verify="required" placeholder="请输入用户名" autocomplete="off" class="layui-input">
      </div>
    </div>
    <div class="layui-form-item">
      <label class="layui-form-label">密码</label>
      <div class="layui-input-block">
        <input type="password" name="password" required lay-verify="required" placeholder="请输入密码" autocomplete="off" class="layui-input">
      </div>
    </div>
    <div class="layui-form-item">
      <div class="layui-input-block">
        <button class="layui-btn" lay-submit lay-filter="loginSubmit">登录</button>
        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
      </div>
    </div>
  </form>
</div>
</body>
</html>