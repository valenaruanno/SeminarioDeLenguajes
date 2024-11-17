import React,{ useState, useEffect} from 'react';
import FooterComponent from '../../components/FooterComponent';
import HeaderComponent from '../../components/HeaderComponent';
import NavbarComponent from '../../components/NavbarComponent';
import PropiedadComponent from '../../components/PropiedadComponent';
const PropiedadPage = () => {
    return (
        <div>
            <HeaderComponent/>
            <NavbarComponent/>
            <PropiedadComponent/>
            <FooterComponent/>
        </div>
    )
}

export default PropiedadPage