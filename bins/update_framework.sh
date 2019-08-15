#! /usr/bin/bash 
# 运行该命令必须在项目的根目录，即在紧挨着 Framework 文件夹的外层。
dev_base_git_url='https://github.com/pureisle/MicroMVC.git'
tmp_git_dir='./tmp_git______'
# 不同步的文件或文件夹
declare -A unsync_set=([composer.json]=0 [README.md]=1 [vendor]=1)

echo "begin clone remote git."
git clone $dev_base_git_url $tmp_git_dir

for e in `ls ${tmp_git_dir}` 
do
    if [ "${unsync_set[$e]}" == "1" ] 
    then
        echo ${e}' escape'
    else
        echo ${e}' sync'
        rsync -a --delete ${tmp_git_dir}'/'${e} ./
    fi
done

echo "clean cache."
rm -rf $tmp_git_dir
# 需要自行添加无需变更的checkout
git checkout 'Framework/config'
git checkout 'composer.json'
echo "update done!"
