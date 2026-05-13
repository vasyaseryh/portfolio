using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Navigation;
using System.Windows.Shapes;

namespace service
{
    /// <summary>
    /// Логика взаимодействия для MainWindow.xaml
    /// </summary>
    public partial class MainWindow : Window
    {
        public MainWindow()
        {
            InitializeComponent();
            
        }



        private void Vxod(object sender, RoutedEventArgs e)
        {
            Пользователи user = serviceEntities3.GetContext().Пользователи.FirstOrDefault(el => el.логин == Login.Text && el.пароль == Password.Text);
            if(user == null)
            {
                MessageBox.Show("Пользователь не найден");
            }
            else
            {
                User.user = user;
                if(user.роль == "админ")
                {
                    new MainAdminWindow().Show();
                    this.Close();
                }
                else if (user.роль == "пользователь")
                {
                    User.client = serviceEntities3.GetContext().Клиенты.FirstOrDefault(el => el.id_пользователя == user.id);
                    new MainUserWindow().Show();
                    this.Close();
                }
                else if(user.роль == "мастер")
                {
                    User.master = serviceEntities3.GetContext().Мастера.FirstOrDefault(el => el.id_пользователя == user.id);
                    new MainMasterWindow().Show();
                    this.Close();
                }
            }
        }

        private void VxodAsGuest(object sender, RoutedEventArgs e)
        {
            new MainGuestWindow().Show();
            this.Close();
        }
    }
}
