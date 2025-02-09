# Makefile pour automatiser les tâches Docker

# Variables
IMAGE_NAME := kyllian37100/cinephoria
TAG := latest

# Construire l'image Docker (x86_64)
build:
	@docker build --platform linux/amd64 -t $(IMAGE_NAME):$(TAG) .

# Pusher l'image sur Docker Hub
push:
	@docker push $(IMAGE_NAME):$(TAG)

# Puller l'image sur Docker Hub
pull:
	@docker pull $(IMAGE_NAME):$(TAG)

# Commande par défaut
.PHONY: build push pull