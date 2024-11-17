import React, { useState, useEffect } from 'react';
import FooterComponent from '../../components/FooterComponent';
import HeaderComponent from '../../components/HeaderComponent';
import NavbarComponent from '../../components/NavbarComponent';
import TipoPropiedadComponent from '../../components/TipoPropiedadComponent';
const TipoPropiedadPage = ()=> {
    return (
        <div>
            <HeaderComponent/>
            <NavbarComponent/>
            <TipoPropiedadComponent/>
            <FooterComponent/>
        </div>
    )
}

export default TipoPropiedadPage