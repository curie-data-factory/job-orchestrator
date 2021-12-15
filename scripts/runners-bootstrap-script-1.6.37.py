# THIS SOFTWARE IS GIVEN "AS IS" WITH NO WARANTY & LIABILITY
# MIT LICENSE SOFTWARE

# Ce Script est le script d'initialisation des container **runner** :
# - Il permet de pull les executables depuis nexus
# - Il cherche les fichiers a executer (py,jar,zip,r)
# - Il execute la commandline .sh pour les zip ou execute le .py, .jar et .r avec la commande python / java -jar / Rscript

# Armand Leopold
# 19/07/2021
scriptVersion = "1.6.37"

import base64
import logging
import os
import subprocess
import sys
from datetime import datetime

import requests
import urllib3

urllib3.disable_warnings()


def root_folder_definition_path_checker(root_folder):
    if root_folder[-1] == "/":
        return root_folder
    else:
        root_folder += "/"
        return root_folder


# Fonction qui va récupérer la ressource sur Nexus
def job_download(job_url):
    file_name = job_url.split("/")[-1]
    logger.info("Downloading : " + job_url)
    try:
        r = requests.get(job_url, auth=(userNexus, passwordNexus), verify=False)
        if r.status_code == 200:
            with open(rootFolder + file_name, "wb") as f:
                f.write(r.content)
        else:
            logger.error("Failed Downloading " + job_url)
            raise
    except:
        logger.error(
            "Error downloading job "
            + job_url
            + " make sure it is present in the repository."
        )
        raise


def sh_finder(rootfolder):
    logger.info("Search commandline on : " + rootfolder)
    for dirpath, subdirs, files in os.walk(rootfolder):
        for sh_file in files:
            if sh_file.endswith(".sh"):
                fullpath = os.path.abspath(dirpath + "/" + sh_file)
                logger.info(
                    "Commande d'execution trouvée : "
                    + sh_file
                    + " ( "
                    + fullpath
                    + " ) \n"
                )
                os.chdir(str(dirpath))
                return "bash " + fullpath + " " + contextArguments


if __name__ == "__main__":

    logger = logging.getLogger("Bootstrap")
    logger.setLevel(logging.INFO)
    ch = logging.StreamHandler()
    ch.setLevel(logging.INFO)
    formatter = logging.Formatter(
        "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
    )
    ch.setFormatter(formatter)
    logger.addHandler(ch)

    logger.info("*********************************************************")
    logger.info("***             Runners Bootstrap Script              ***")
    logger.info("*********************************************************")
    logger.info(
        "Start Runner at  " + datetime.now().strftime("%d-%m-%Y %H:%M:%S") + " : "
    )
    logger.info("Version of runner :" + scriptVersion)
    logger.info("Recovering environnment variables.")

    debug = "False"
    try:
        debug = os.environ["DEBUG"]
    except KeyError:
        pass

    try:
        jobs = os.environ["JOBTORUN"]
        rootFolder = os.environ["ROOTFOLDER"]
        userNexus = os.environ["USERNEXUS"]
        passwordNexus = os.environ["PASSWORDNEXUS"]
    except:
        logger.error(
            "ERROR GETTING JOBTORUN ENVIRONMENT VARIABLE, please check if it is correctly setup !"
        )
        raise

    try:
        contextArguments = os.environ["CONTEXTARGUMENTS"]
    except KeyError:
        logger.info("Pas d'arguments à ajouter au contexte d'execution.")
        contextArguments = ""

    if contextArguments != "":
        logger.info("Il y a des arguments à ajouter au contexte d'execution.")
        contextArguments = base64.b64decode(contextArguments).decode("utf-8")
        if debug == "True":
            logger.info("Arguments : " + contextArguments)

    logger.info("Recovering CIFS mounting environnment variables.")
    try:
        cifsUser = os.environ["CIFSUSER"]
        cifsPassword = os.environ["CIFSPASSWORD"]
        cifsEndpoint = os.environ["CIFSENDPOINT"]
        cifsDomain = os.environ["CIFSDOMAIN"]
        cifsSource = os.environ["CIFSSOURCE"]
        cifsDest = os.environ["CIFSDEST"]

        logger.info(
            "Mounting path : //" + cifsEndpoint + cifsDest + " <<-->> " + cifsSource
        )
        subprocess.run('echo "username=' + cifsUser + '" > /root/.smbcred ', shell=True)
        subprocess.run(
            'echo "password=' + cifsPassword + '" >> /root/.smbcred', shell=True
        )
        subprocess.run('echo "domain=' + cifsDomain + '" >> /root/.smbcred', shell=True)
        logger.info(
            "mount -vvv -t cifs -o noperm,credentials=/root/.smbcred //"
            + cifsEndpoint
            + cifsDest
            + " "
            + cifsSource
        )
        subprocess.run("mkdir -p " + cifsSource, shell=True)
        subprocess.run(
            "mount -vvv -t cifs -o noperm,credentials=/root/.smbcred //"
            + cifsEndpoint
            + cifsDest
            + " "
            + cifsSource,
            shell=True,
        )
    except:
        pass

    logger.info("Recovering CIFS-1 mounting environnment variables.")
    try:
        cifsUser1 = os.environ["CIFSUSER1"]
        cifsPassword1 = os.environ["CIFSPASSWORD1"]
        cifsEndpoint1 = os.environ["CIFSENDPOINT1"]
        cifsDomain1 = os.environ["CIFSDOMAIN1"]
        cifsSource1 = os.environ["CIFSSOURCE1"]
        cifsDest1 = os.environ["CIFSDEST1"]

        logger.info(
            "Mounting path : //" + cifsEndpoint1 + cifsDest1 + " <<-->> " + cifsSource1
        )
        subprocess.run(
            'echo "username=' + cifsUser1 + '" > /root/.smbcred ', shell=True
        )
        subprocess.run(
            'echo "password=' + cifsPassword1 + '" >> /root/.smbcred', shell=True
        )
        subprocess.run(
            'echo "domain=' + cifsDomain1 + '" >> /root/.smbcred', shell=True
        )
        logger.info(
            "mount -vvv -t cifs -o noperm,credentials=/root/.smbcred //"
            + cifsEndpoint1
            + cifsDest1
            + " "
            + cifsSource1
        )
        subprocess.run("mkdir -p " + cifsSource1, shell=True)
        subprocess.run(
            "mount -vvv -t cifs -o noperm,credentials=/root/.smbcred //"
            + cifsEndpoint1
            + cifsDest1
            + " "
            + cifsSource1,
            shell=True,
        )
    except:
        pass

    # Essais extraction array : (cas multiples jobs)
    isArrayJobExec = False
    try:
        arrayjobs = jobs.split(",")
        if len(arrayjobs) > 1:
            isArrayJobExec = True
            logger.info("Multiple Job Runner : " + str(jobs))
        elif len(arrayjobs) == 1:
            logger.info("Single Job Runner : " + str(jobs))
            isArrayJobExec = False
            job = jobs
    except:
        logger.error("Error splitting jobs URL ressources : " + jobs)
        raise

    rootFolder = root_folder_definition_path_checker(rootFolder)

    # Downloading ressources
    if isArrayJobExec:
        for job in arrayjobs:
            job_download(job)
    else:
        job_download(jobs)

    logger.info(
        "Download finished at " + datetime.now().strftime("%d-%m-%Y %H:%M:%S") + " : "
    )

    listFolders = os.listdir(rootFolder)
    listFolders.sort()
    for file in listFolders:

        executeLine = ""
        if file.endswith(".zip"):
            logger.info("Zip File found at > " + file + " < unzipping it.")
            os.system("unzip -q -o " + file + " -d " + file[:-4])
            logger.info("unzip -q -o " + file + " -d " + file[:-4])
            executeLine = sh_finder(rootFolder + file[:-4] + "/")

        elif file.endswith(".jar"):
            logger.info("Java Jar file found at > " + file + " < Running it.")
            logger.info("Running script : " + file)
            os.chdir(str(rootFolder))
            executeLine = "java -jar " + rootFolder + file + " " + contextArguments

        elif file.endswith(".py"):
            logger.info("Python script file found at > " + file + " < Running it.")
            logger.info("Running script : " + file)
            os.chdir(str(rootFolder))
            executeLine = "python " + rootFolder + file + " " + contextArguments

        elif file.endswith(".r"):
            logger.info("R script file found at > " + file + " < Running it.")
            logger.info("Running script : " + file)
            os.chdir(str(rootFolder))
            executeLine = "Rscript " + rootFolder + file + " " + contextArguments

        else:
            logger.warning(
                "File not in the supported extension scope of executables, skipping it > "
                + file
            )

        # subprocess execute :
        process = subprocess.Popen(executeLine, shell=True, text=True)
        stdout, stderr = process.communicate()
        if process.returncode != 0:
            sys.exit(process.returncode)

    logger.info("*********************************************************")
    logger.info("***                        FIN                        ***")
    logger.info("*********************************************************")
