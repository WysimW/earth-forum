// src/components/Slider/Slider.js
import React from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import './Slider.css';

const Slider = () => {
    return (
        <div className="slider-container">
            <Swiper
                modules={[Navigation, Pagination, Autoplay]}
                spaceBetween={50}
                slidesPerView={1}
                autoplay={{ delay: 3000 }}
                loop={true}
                pagination={{ clickable: true }}
                navigation
            >
                <SwiperSlide>
                    <div className="slide">
                        <img src="https://cdn.midjourney.com/f0d383e0-e263-41eb-9668-37e7356d79a0/0_2.png" alt="Banner 1" className="slide-banner" />
                        <div className="slide-content">
                            <h2 className="slide-title">New event: Dark Crisis!</h2>
                            <p className="slide-description">Experience our new dark mode feature, perfect for night-time browsing.</p>
                        </div>
                    </div>
                </SwiperSlide>
                <SwiperSlide>
                    <div className="slide">
                        <img src="https://via.placeholder.com/1200x300" alt="Banner 2" className="slide-banner" />
                        <div className="slide-content">
                            <h2 className="slide-title">Check out the latest updates</h2>
                            <p className="slide-description">Stay informed with all the latest changes and updates on our platform.</p>
                        </div>
                    </div>
                </SwiperSlide>
                <SwiperSlide>
                    <div className="slide">
                        <img src="https://via.placeholder.com/1200x300" alt="Banner 3" className="slide-banner" />
                        <div className="slide-content">
                            <h2 className="slide-title">Join our community events</h2>
                            <p className="slide-description">Participate in upcoming events and connect with fellow members.</p>
                        </div>
                    </div>
                </SwiperSlide>
            </Swiper>
        </div>
    );
};

export default Slider;
