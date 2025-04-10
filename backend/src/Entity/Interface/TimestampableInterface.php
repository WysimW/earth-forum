<?php

namespace App\Entity\Interface;

interface TimestampableInterface
{
    public function getCreatedAt(): ?\DateTimeInterface;
    public function setCreatedAt(\DateTimeInterface $createdAt): static;
    public function getUpdatedAt(): ?\DateTimeInterface;
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static;
}