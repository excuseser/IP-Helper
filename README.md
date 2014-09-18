IP-helper
===================================
  这是为了ip-helper制作的简单工具，透过server host 的ping命令，测试target host 的状况。现在只提供了php的版本。

  
### Usage
- Download files and upload to your server
- 打开浏览器访问 tools.php 当然你也可以改名
- 若出现以下界面，表示安装成功。
- 在IP Helper App中添加server
  <p align="center" ><img src="http://www.sailcore.com/iphelper/help.jpg" alt="help" title="IP-helper"></p>
- 通过server ping下target host


### Requirements
Php>5.2x and可以用exec()
检查 php.ini ，让 disable_functions 中没有exec function
如果Win下遇到unable to fork... 则将cmd.exe 添加IUSR_ComputerName的用户权限

### Testing environment
- Windows XP
- Windows 2003
- Windows 7
- Redhat 6
- Centos 7

### Contact
excuseser@gmail.com


### License
is available under the MIT license. See the LICENSE file for more info.
