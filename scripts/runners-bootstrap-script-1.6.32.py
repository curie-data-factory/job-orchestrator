# THIS SOFTWARE IS GIVEN "AS IS" WITH NO WARANTY & LIABILITY
# MIT LICENSE SOFTWARE

# # Ce Script est le script d'initialisation des container **runner** :
# - Il permet de pull les executables depuis nexus
# - Il cherche les fichiers a executer (py,jar,zip)
# - Il execuse la commandline .sh pour les zip ou execute le .py et .jar avec la commande python / java -jar

# Armand Leopold
# 05/03/2021

scriptVersion = "1.6.32"

import os
import sys
import subprocess
import time
import datetime
import json
import requests
import urllib3
import base64
from datetime import datetime
from time import strftime
urllib3.disable_warnings()

def logMsg(message):
    dt = datetime.now()
    return {"date":strftime("%d/%m/%Y %H:%M:%S"),"message":message}

def rootFolderDefinitionPathChecker(rootFolder):
    if(rootFolder[-1] == "/"):
        return rootFolder
    else:
        rootFolder += "/"
        return rootFolder

# Fonction qui va récupérer la ressource sur Nexus
def jobDownload(jobURL):
    fileName = jobURL.split("/")[-1]
    print('[INFO] Downloading : '+jobURL)
    try:
        r = requests.get(jobURL,auth=(userNexus,passwordNexus),verify=False)
        if(r.status_code == 200):
            with open(rootFolder+fileName, 'wb') as f:
                f.write(r.content)
        else:
            print("[ERROR] Failed Downloading "+jobURL)
    except:
        print("[ERROR] Error downloading job "+jobURL+" make sure it is present in the repository.")

def shFinder(rootfolder):
    print("[INFO] Search commandline on : "+rootfolder)
    for dirpath, subdirs, files in os.walk(rootfolder):
        for file in files:
            if file.endswith('.sh'):
                fullpath = os.path.abspath(dirpath+"/"+file)
                print("[INFO] Found commandline at : "+file+" ( "+fullpath+" ) \n")
                os.chdir(str(dirpath))
                return "bash "+fullpath+" "+contextArguments


if __name__ == "__main__":

    print("*********************************************************")
    print("***             Runners Bootstrap Script              ***")
    print("*********************************************************")
    logs = []   
    print("[INFO] Start Runner at  "+datetime.now().strftime("%d-%m-%Y %H:%M:%S")+" : ")
    logs.append(logMsg("[INFO] Start Runner at  "+datetime.now().strftime("%d-%m-%Y %H:%M:%S")))
    print("[INFO] Version of runner :"+scriptVersion)
    logs.append(logMsg("[INFO] Version of runner :"+scriptVersion))
    print("[INFO] Recovering environnment variables.")
    
    debug="False"
    try:
        debug = os.environ['DEBUG']
    except:
        a=1
        
    try:
        jobs = os.environ['JOBTORUN']
        rootFolder = os.environ['ROOTFOLDER']
        userNexus = os.environ['USERNEXUS']
        passwordNexus = os.environ['PASSWORDNEXUS']
    except:
        print("[ERROR] ERROR GETTING JOBTORUN ENVIRONMENT VARIABLE, please check if it is correctly setup !")
        raise
        sys.exit()

    try:
        contextArguments = os.environ['CONTEXTARGUMENTS']
    except:
        print("[INFO] No arguments to add to execution context.")
        contextArguments = ""
        
    if(contextArguments != ""):
        print("[INFO] There are arguments to be added to the execution context.")
        contextArguments = base64.b64decode(contextArguments).decode('utf-8')
        if(debug == "True"):
            print("[INFO] Arguments : "+contextArguments)
        
    print("[INFO] Recovering CIFS mouting environnment variables.")
    try:
        cifsUser = os.environ['CIFSUSER']
        cifsPassword = os.environ['CIFSPASSWORD']
        cifsEndpoint = os.environ['CIFSENDPOINT']
        cifsDomain = os.environ['CIFSDOMAIN']
        cifsSource = os.environ['CIFSSOURCE']
        cifsDest = os.environ['CIFSDEST']

        print('[INFO] Mounting path : //'+cifsEndpoint+cifsDest+' <<-->> '+cifsSource)
        subprocess.run('echo "username='+cifsUser+'" > /root/.smbcred ', shell=True)
        subprocess.run('echo "password='+cifsPassword+'" >> /root/.smbcred', shell=True)
        subprocess.run('echo "domain='+cifsDomain+'" >> /root/.smbcred', shell=True)
        print("[INFO] "+'mount -vvv -t cifs -o noperm,credentials=/root/.smbcred //'+cifsEndpoint+cifsDest+' '+cifsSource)
        subprocess.run('mkdir -p '+cifsSource, shell=True)
        subprocess.run('mount -vvv -t cifs -o noperm,credentials=/root/.smbcred //'+cifsEndpoint+cifsDest+' '+cifsSource, shell=True)
    except:
        a=1
        
    # Essais extraction array : (cas multiples jobs)
    isArrayJobExec = False
    try:
        arrayjobs = jobs.split(',')
        if(len(arrayjobs) > 1):
            isArrayJobExec = True
            print("[INFO] Multiple Job Runner : "+str(jobs))
        elif(len(arrayjobs) == 1):
            print("[INFO] Single Job Runner : "+str(jobs))
            isArrayJobExec = False
            job = jobs
    except:
        print("[ERROR] Error spliting jobs URL ressources : "+jobs)
        raise
        sys.exit()
        
    rootFolder = rootFolderDefinitionPathChecker(rootFolder)
    
    # Downloading ressources 
    if(isArrayJobExec):
        for job in arrayjobs:
            jobDownload(job)
    else:
        jobDownload(jobs)

    print("[INFO] Donwload finished at "+datetime.now().strftime("%d-%m-%Y %H:%M:%S")+" : ")
    logs.append(logMsg("[INFO] Donwload finished at  "+datetime.now().strftime("%d-%m-%Y %H:%M:%S")))
    
    listFolders = os.listdir(rootFolder)
    listFolders.sort()
    for file in listFolders:
        
        if file.endswith(".zip"):
            print("[INFO] Zip File found at > "+file+" < unziping it.")
            os.system("unzip -q -o "+file+" -d "+file[:-4])
            print("[INFO] unzip -q -o "+file+" -d "+file[:-4])
            executeLine = shFinder(rootFolder+file[:-4]+"/")
            
        elif file.endswith(".jar"):
            print("[INFO] Java Jar file found at > "+file+" < Running it.")
            logs.append(logMsg("[INFO] Running script : " +file))
            os.chdir(str(rootFolder))
            executeLine = 'java -jar '+rootFolder+file+" "+contextArguments
            
        elif file.endswith(".py"):
            print("[INFO] Python script file found at > "+file+" < Running it.")
            logs.append(logMsg("[INFO] Running script : " +file))
            os.chdir(str(rootFolder))
            executeLine = 'python '+rootFolder+file+" "+contextArguments
            
        else:
            print("[WARNING] File not in the supported extension scope of executables, skiping it > "+ file)
            sys.exit()
        
        # subprocess execute :
        process = subprocess.Popen(executeLine, shell=True,text=True)
        stdout, stderr = process.communicate()
        if process.returncode != 0:
            raise
            sys.exit()
                
    print("*********************************************************")
    print("***                        FIN                        ***")
    print("*********************************************************")