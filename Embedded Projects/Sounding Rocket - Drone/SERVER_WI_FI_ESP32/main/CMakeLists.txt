#Dice al compilatore come compilare il componente main:
#SRCS "main.c" → Quale file compilare
#INCLUDE_DIRS "." → Dove cercare gli header del componente main
#PRIV_INCLUDE_DIRS "../components/mavlink/all" → Dove cercare gli header di MAVLink quando si compila main.c

idf_component_register(SRCS "main.c"
                       INCLUDE_DIRS "."
                       PRIV_INCLUDE_DIRS "../components/mavlink/all")
                       

target_include_directories(${COMPONENT_LIB} PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/../components/mavlink/all)