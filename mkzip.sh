#!/bin/bash
mkdir build/

cd build

ln -sf ../LICENSE .
ln -sf ../INSTALL .
ln -sf ../README.txt README.txt

#simple install
mkdir -p 1.simple.install/
mkdir -p 1.simple.install/app/
mkdir -p 1.simple.install/app/code/local/Metrof/FBConnect/
mkdir -p 1.simple.install/app/design/frontend/default/default/layout/
mkdir -p 1.simple.install/app/design/frontend/default/default/template/
mkdir -p 1.simple.install/app/etc/modules
ln -sf ../../../../../Metrof_FBConnect.xml 1.simple.install/app/etc/modules/
ln -sf ../../../../../../../../design/frontend.layout/metrof_fbc.xml 1.simple.install/app/design/frontend/default/default/layout/
ln -sf ../../../../../../../../design/frontend.templates/fbconnect 1.simple.install/app/design/frontend/default/default/template/
#locale
ln -sf ../../../locale/ 1.simple.install/app/

#main module
ln -sf ../../../../../../../controllers 1.simple.install/app/code/local/Metrof/FBConnect/
ln -sf ../../../../../../../etc 1.simple.install/app/code/local/Metrof/FBConnect/
ln -sf ../../../../../../../design 1.simple.install/app/code/local/Metrof/FBConnect/
ln -sf ../../../../../../../Helper 1.simple.install/app/code/local/Metrof/FBConnect/
ln -sf ../../../../../../../locale 1.simple.install/app/code/local/Metrof/FBConnect/
ln -sf ../../../../../../../Metrof_FBConnect.xml 1.simple.install/app/code/local/Metrof/FBConnect/
ln -sf ../../../../../../../sql 1.simple.install/app/code/local/Metrof/FBConnect/




mkdir -p 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../controllers 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../etc 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../design 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../Helper 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../locale 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../sql 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../Metrof_FBConnect.xml 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../INSTALL 2.advanced.install/Metrof/FBConnect/
ln -sf ../../../../LICENSE 2.advanced.install/Metrof/FBConnect/


zip -r ../../../Metrof_FBConnect_magento-1.2.3.zip . -x "*~"
#cd ../../
#zip -r Metrof_FBConnect_magento-1.2.2.zip Metrof/FBConnect/ -x "Metrof/FBConnect/.git*" -x "*~" -x "Metrof/FBConnect/mkzip.sh" -x "Metrof/FBConnect/test_res.php"
