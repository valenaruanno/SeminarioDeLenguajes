import React, { useState, useEffect} from 'react';
import FooterComponent from '../../components/FooterComponent';
import HeaderComponent from '../../components/HeaderComponent';
import NavbarComponent from '../../components/NavbarComponent';
import LocalidadComponent from '../../components/LocalidadComponent';
const LocalidadPage = () => {
    return (
        <div>
            <HeaderComponent/>
            <NavbarComponent/>
            <LocalidadComponent/>
            <FooterComponent/>
        </div>
    )
}

export default LocalidadPage