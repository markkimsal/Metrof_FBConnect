#!/bin/bash
cd ../../
zip -r Metrof_FBConnect_magento-1.0.6.zip Metrof/FBConnect/ -x "Metrof/FBConnect/.git*" -x "*~" -x "Metrof/FBConnect/mkzip.sh"
