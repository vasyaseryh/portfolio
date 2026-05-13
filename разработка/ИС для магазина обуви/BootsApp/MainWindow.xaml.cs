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

namespace BootsApp
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

        private void Button_Click(object sender, RoutedEventArgs e)
        {
            GuestMainWindow gw = new GuestMainWindow();
            gw.Show();
            this.Close();
        }

        private void Button_Click_1(object sender, RoutedEventArgs e)
        {
            user_import user = bootsEntities2.GetContext().user_import.FirstOrDefault(el => el.Логин == Login.Text && el.Пароль == Password.Text);
            if (user == null)
            {
                MessageBox.Show("Пользователь не найден");
            }
            else
            {
                if (user.роли.Роль == "Администратор")
                {
                    User.user = user;
                    AdminMainWindow aw = new AdminMainWindow();
                    aw.Show();
                    this.Close();
                }
                if (user.роли.Роль == "Менеджер")
                {
                    User.user = user;
                    ManegerMainWindow mw = new ManegerMainWindow();
                    mw.Show();
                    this.Close();
                }
                if (user.роли.Роль == "Авторизованный клиент")
                {
                    User.user = user;
                    UserMainWindow uw = new UserMainWindow();
                    uw.Show();
                    this.Close();
                }
            }
        }
    }
}
