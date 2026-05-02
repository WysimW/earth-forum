import React from 'react';
import { Link } from 'react-router-dom';
import Layout from '../../components/Layout/Layout';
import styles from './About.module.css';

const About = () => {
  return (
    <Layout>
      <div className={styles.content}>
        <header className={styles.header}>
          <h1 className={styles.title}>Qui sommes-nous</h1>
          <p className={styles.subtitle}>
            Découvrez l'histoire et la communauté d'Earth Forum
          </p>
        </header>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Notre histoire</h2>
          <div className={styles.textContent}>
            <p>
              Earth Forum est né de la passion pour le roleplay et les univers de comics. 
              Notre communauté s'est formée autour d'un désir commun : créer un espace 
              où les fans peuvent explorer leurs univers favoris à travers des récits 
              collaboratifs et immersifs.
            </p>
            <p>
              Depuis nos débuts, nous avons construit une plateforme qui rassemble 
              des passionnés de DC Comics, Marvel, Star Wars et bien d'autres univers. 
              Chaque forum est un monde à part entière, où l'imagination n'a pas de limites.
            </p>
          </div>
        </section>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Notre mission</h2>
          <div className={styles.textContent}>
            <p>
              Notre mission est de fournir une expérience de roleplay de qualité, 
              dans un environnement respectueux et créatif. Nous croyons que chaque 
              membre de notre communauté a quelque chose d'unique à apporter.
            </p>
            <p>
              Nous nous engageons à maintenir un espace sûr et inclusif, où chacun 
              peut s'exprimer librement tout en respectant les autres membres et 
              les univers que nous explorons ensemble.
            </p>
          </div>
        </section>

        <section className={styles.section}>
          <h2 className={styles.sectionTitle}>Rejoignez-nous</h2>
          <div className={styles.textContent}>
            <p>
              Que vous soyez un roleplayer expérimenté ou débutant, vous êtes le 
              bienvenu sur Earth Forum. Notre communauté grandit chaque jour, et nous 
              serions ravis de vous compter parmi nous.
            </p>
            <p>
              Créez votre compte dès aujourd'hui et plongez dans les univers qui 
              vous passionnent. L'aventure vous attend !
            </p>
            <div className={styles.actions}>
              <Link to="/register" className={styles.button}>
                Créer un compte
              </Link>
              <Link to="/" className={styles.buttonSecondary}>
                Explorer les univers
              </Link>
            </div>
          </div>
        </section>
      </div>
    </Layout>
  );
};

export default About;

