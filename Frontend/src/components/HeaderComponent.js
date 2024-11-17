import React from 'react';
import imagen from "../assets/images/inmobiliarias.jpg"
const HeaderComponent = ()=> {
    return (
        <>
            <div>  
            <header> 
                <h1> Inmobiliaria </h1>              
                <img src = {imagen} alt = "logo"/>            
            </header>
        </div>
    </>
    );
  
}

export default HeaderComponent; 

